<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\GameSession;
use App\Models\User;
use App\Models\WTournament;
use App\Models\HoldCredits;

use DB;
use Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Carbon\Carbon;

use Illuminate\Http\Request;
 
class TelegramBotController extends Controller
{
	
	public function handleRequest(Request $request)
    {
		$update = Telegram::commandsHandler(true);
		if($update->detectType() === 'callback_query')
		{
			$this->answerCallbackQuery($update);
			return;
		}
		
		if($update->detectType() === 'chosen_inline_result') return;
		if($update->detectType() === 'inline_query')
		{
			$this->answerInlineQuery($update);
			return;
		}
		
		if(isset($request['score'])) {
			$this->setGameScore($request);
			return;
		}

		if( !isset($update->message->entities[0]["type"]) && ($update->detectType() !== 'edited_message') ){
			$step = TelegramBot::getStep($update->message->from->id);
			if( isset($step) && count(TelegramBot::$arrEditUser) >= $step+1){
				User::where('telegram_id', $update->message->from->id)->update([TelegramBot::$arrEditUser[$step] => $update->message->text]);
				TelegramBot::where('telegram_id', $update->message->from->id)->update(['step' => $step+1]);
				$text = TelegramBot::$arrEditUserText[$step+1];
				if(count(TelegramBot::$arrEditUser) == $step+1) TelegramBot::where('telegram_id', $update->message->from->id)->delete();
				return Telegram::sendMessage(['chat_id' => $update->message->chat->id,'text' => $text]);
			}
			return;
		}
	}
	
	protected function answerCallbackQuery($update)
    {
		$session = array();
		$callback_query = $update->callback_query;
		$user = $callback_query->from;
		$message = $callback_query->message;
		
		$user_id = $user->id ?? null;
		$chat_id = $message->chat->id ?? null;
		$message_id = $message->message_id ?? null;
		$inline_message_id = $callback_query->inline_message_id ?? null;
		$game_short_name = $callback_query->game_short_name ?? null;
		
		// проверка пользователя в базе, если нет - сохраняем
		$user = TelegramBot::checkDatabase($callback_query);
		if($inline_message_id) $session = GameSession::tsOpen($user_id, $inline_message_id, $game_short_name);
		if($game_short_name == 'quickmath_10') $session = WTournament::open($user, $game_short_name, $message_id);
		
		$data = [
			'callback_query_id' => $callback_query->id,
			'show_alert' 		=> true,
		];

		if(isset($session['message'])){
			$data['text'] = $session['message'];
		}else{
			$data['url'] = env('GAME_HOST').'/index.html?user_id='.$user_id.'&inline_message_id='.$inline_message_id.'&chat_id='.$chat_id.'&message_id='.$message_id;
		}
		
		return Telegram::answerCallbackQuery($data);
	}
	
    protected function setGameScore($req)
    {
        $data = [
            'user_id' => $req['user_id'],
			'score' => ($req['score']) ? $req['score'] : 0,
			'force' => (!empty($data['inline_message_id'])) ? true : false,
        ];
		if($req['inline_message_id']) $data['inline_message_id'] = $req['inline_message_id'];
		if($req['message_id']) $data['message_id'] = $req['message_id'];
		if($req['chat_id']) $data['chat_id'] = $req['chat_id'];
		
		if(isset($data['message_id'])) {
			$mes = WTournament::close($data);
			Telegram::sendMessage(['text' => $mes, 'chat_id' => $data['chat_id'], 'reply_to_message_id' => $data['message_id'], 'parse_mode' => 'html']);
		}
		Telegram::setGameScore($data);

		if(!empty($data['inline_message_id'])){
			$message = GameSession::saveScoreDB($data['user_id'], $data['inline_message_id'], $data['score']);
			return  Telegram::editMessageText(['text' => $message, 'inline_message_id' => $data['inline_message_id'], 'parse_mode' => 'html']);
		}
		return;
    }
	
    protected function answerInlineQuery($update)
    {
		$results = [
			[
				'type' => 'game',
				'id' => '1',
				'game_short_name' => 'quickmath_100',
				//'reply_markup' => $reply_markup,
			],
			[
				'type' => 'article',
				'id' => '2',
				'title' => 'Пополнить баланс',
				"input_message_content" => ['message_text' => '<a href="'.env('TELEGRAM_URL').'/auth/'.$update->inline_query->from->id.'">Пополнить баланс</a>', 'parse_mode' => 'HTML', 'disable_web_page_preview' => true],
			]
		];
        $data = [
            'inline_query_id' => $update->inline_query->id,
			'results' => json_encode($results),
			'switch_pm_text' => 'Перейти в GameChaingerBot',
			'switch_pm_parameter' => "{$update->inline_query->id}"
        ];
		
		return Telegram::answerInlineQuery($data);
    }
	
	public function tournamentLeaders()
	{
		$games = \App\Models\Game::all();
		return view('telegram.table_leaders', compact('games', 'payment_history'))->with(['leaders' => WTournament::getLeaders()]);
	}
	
	public function message()
	{
		$games = \App\Models\Game::all();
		$users = User::whereNotNull('telegram_id')->get();
		return view('telegram.message', compact('games', 'payment_history'))->with(['users' => $users]);
	}
	
	public function sendMessage(Request $request)
	{
		if(isset($request->message_all)){
			$users = User::whereNotNull('telegram_id')->get();
			foreach($users as $user){
				try {
					Telegram::sendMessage(['text' => $request->message_all, 'chat_id' => $user->telegram_id]);
				} catch (TelegramResponseException $e) {

					/*$errorData = $e->getResponseData();

					if ($errorData['ok'] === false) {
						Telegram::sendMessage([
							'chat_id' => '675198135',
							'text'    => 'There was an error for a user. ' . $errorData['error_code'] . ' ' . $errorData['description'],
						]);
					}*/
					continue;
				}
			}
		}else{
			Telegram::sendMessage(['text' => $request->message, 'chat_id' => $request->telegram_id]);
		}
		
		return redirect("/telegram/message");
	}
	
	public function test()
	{
		
	}
}
