<?php
namespace App\Models;

use App\Helpers\LoggerHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Models\HoldCredits;
use App\Models\Profit;
/**
 * Class Game
 * @package App
 */
class GameSession extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_sessions';
    /**
     * @var array
     */
    protected $fillable = [
        'winner_id',
        'bet_id',
        'game_id',
        'win',
        'uuid',
        'started_at',
        'ended_at'
    ];


    public function bet()
    {
        return $this->belongsTo(\App\Models\GameBet::class);
    }

    public function game()
    {
        return $this->belongsTo(\App\Models\Game::class);
    }

    public function users_sessions()
    {
        return $this->hasMany(\App\Models\GameSessionUser::class, 'session_id', 'id');
    }
	
	
    public function winner()
    {
        return $this->hasOne(\App\Models\User::class, 'winner_id', 'id');
    }

    public function scopeNotPlayed()
    {

    }

    /**
     * @param $betId
     * @param $userId
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public static function open($betId, $userId)
    {
        return DB::transaction(function () use ($betId, $userId){
            //проверяем есть ли такая ставка у этой игры
            $gameBet = GameBet::findOrFail($betId);
            /**
             * @var User $user
             */
            $user = User::findOrFail($userId);
            /**
             * @var Game $game
             */
            $game = Game::findOrFail($gameBet->game_id);

            //проверяем есть уже сессия для этой игры
//        echo $betId . " \n";
//        echo $userId . " \n";
//        echo " \n";
            $session = self::whereNull('started_at')
                ->whereNull('winner_id')
                ->where('bet_id', $betId)
                ->get()
                ->first();



            if($session){
//            //проверяем нет ли этого пользователя уже в сессии
                $session = self::select([
                        'g.id'
                    ])
                    ->from('game_sessions as g')
                    ->whereNull('g.winner_id')
                    ->whereNull('g.started_at')
                    ->where('g.bet_id', $betId)
                    ->where('u.user_id', $userId)
                    ->from('game_sessions as g')
                    ->leftJoin('game_sessions_users as u', function ($j){
                        $j->on('u.session_id', '=', 'g.id');
                    })
                    ->get()
                    ->first();

                if($session){
                    return $session->id;
                }else{
//                select COUNT(u.session_id) as count, `g`.`id`
//from `game_sessions` as `g`
//left join `game_sessions_users` as `u` on `u`.`session_id` = `g`.`id`
//where `g`.`started_at` is null
//                and `g`.`id` NOT IN (SELECT `game_sessions_users`.`session_id` FROM `game_sessions_users` WHERE `game_sessions_users`.`user_id` = 3)
//and `u`.`user_id` is not null
//group by `g`.`id` having COUNT(u.session_id) < 4


                    $session = self::select(DB::raw('
                        g.id,
                        COUNT(u.session_id) as count
                     '))
                        ->whereNull('g.winner_id')
                        ->whereNull('g.started_at')
                        ->where('g.bet_id', $betId)
                        ->from('game_sessions as g')
                        ->leftJoin('game_sessions_users as u', function ($j){
                            $j->on('u.session_id', '=', 'g.id');
                        })
                        ->whereRaw('g.id NOT IN (SELECT `game_sessions_users`.`session_id` FROM `game_sessions_users` WHERE `game_sessions_users`.`user_id` = ' . $userId . ')')
                        ->groupBy('g.id')
//                        ->havingRaw('count < ' . $game->need_users)
                        ->get()
                        ->first();

                    if($session){
                        GameSessionUser::create([
                            'user_id' => $userId,
                            'session_id' => $session->id,
                            'credits_before' => $user->credits
                        ]);



                        return $session->id;
                    }else{
                        $session = GameSession::create([
                            'bet_id' => $gameBet->id
                        ]);

                        GameSessionUser::create([
                            'user_id' => $userId,
                            'session_id' => $session->id,
                            'credits_before' => $user->credits
                        ]);


                        return $session->id;
                    }
                }
            }else{
                $session = GameSession::create([
                    'bet_id' => $gameBet->id
                ]);

                GameSessionUser::create([
                    'user_id' => $userId,
                    'session_id' => $session->id,
                    'credits_before' => $user->credits
                ]);

                return $session->id;
            }
        });
    }

    /**
     * @param $sessionId
     * @param $userId
     * @return bool
     */
    public static function close($sessionId, $userId)
    {
        /**
         * @var GameSession $session
         */
        $session = GameSession::where('id', $sessionId)
            ->first();


        /**
         * @var GameBet $bet
         */
        $bet = GameBet::where('id', $session->bet_id)
            ->first();

        if($session)
        {
            $win  = $session->getWin();

            $session->update([
                'winner_id' => $userId,
                'ended_at' => Carbon::now(),
                'win' => $win
            ]);

            $gameSessions = GameSessionUser::where('session_id', $sessionId)
                ->get()
                ->all();

            foreach ($gameSessions as $gameSession)
            {
                $user = User::find($gameSession->user_id);
                if($gameSession->user_id == $userId)
                {
                    $user->increment('credits', $win);

                    $gameSession->update([
                        'credits_after' => $user->credits
                    ]);
                }else{
                    $userSess = User::where('id', $gameSession->user_id)
                        ->first();

                    $gameSession->update([
                        'credits_after' => $userSess->credits
                    ]);
                }
            }

            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $sessionId
     * @param $userId
     * @return bool
     */
    public static function exit($sessionId, $userId)
    {
        /**
         * @var GameSession $session
         */
        $session = GameSession::where('id', $sessionId)
            ->whereNull('started_at')
            ->whereNull('ended_at')
            ->first();

        if($session){
            $sessionUser = GameSessionUser::where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->first();


                $client = new \ElephantIO\Client(new \ElephantIO\Engine\SocketIO\Version2X("http://gamechainger.io:8000?user_id=$userId&session_id=$sessionId"));
        
                $client->initialize();
                $client->emit('leave pending game', ['userId' => $userId, 'sessionId' => $sessionId]);
                $client->close();

            if($sessionUser){
                $sessionUser->delete();

                return true;
            }
        }
        return false;
    }

    /**
     * @param $sessionId
     * @return bool
     */
    public static function start($sessionId)
    {
        /**
         * @var GameSession $session
         */
        $session = GameSession::where('id', $sessionId)
            ->whereNull('started_at')
            ->first();

        if($session){
            $sessionUsers = GameSessionUser::where('session_id', $session->id)
                ->get()
                ->all();

            $gameBet = GameBet::findOrFail($session->bet_id);

            foreach ($sessionUsers as $sessionUser)
            {
                $user = User::where('id', $sessionUser->user_id)->first();
                if($user){
                    $user->decrement('credits', $gameBet->bet);
                }
            }

            $session->update([
                'started_at' => Carbon::now()
            ]);

            return true;
        }else{
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getWin()
    {
        try{
            $bet = GameBet::findOrFail($this->bet_id)->bet;

            $count = self::select(DB::raw('
                    COUNT(u.session_id) as count
                 '))
                ->from('game_sessions as g')
                ->where('g.id', $this->id)
                ->leftJoin('game_sessions_users as u', function ($j){
                    $j->on('u.session_id', '=', 'g.id');
                })
                ->groupBy('g.id')
                ->get()
                ->first()->count;

            return $bet * $count;
        }catch (\Exception $e){
            LoggerHelper::getLogger()->error($e);
            return 0;
        }
    }
	
	/**
	 * @param $telegram_id, $uuid, $game_short_name
     * @return $session['id'] or $session['message']
     */	
	public static function tsOpen($telegram_id, $uuid, $game_short_name)
    {
		$game = self::getGameByGameShortName($game_short_name);
		if(empty($game)) {
			$session['message'] = "Игры не существует";
			return $session;
		}

		$user = DB::table('users')->where('telegram_id', $telegram_id)->first();
		$balance = User::getCredits($user->id);
		if((float)$balance < (float)$game->bet) {
			$session['message'] = "На вашем счету не хватает средств! Текущий баланс: {$balance} cr. Для пополнения баланса перейдите в бота @gamechainger_bot";
			return $session;
		}		

		$session = self::where('uuid', $uuid)->first();
		if(!isset($session->id)){
			$session = GameSession::create([
				'bet_id' => $game->bet_id,
				'game_id' => $game->game_id,
				'uuid' => $uuid,
				'started_at' => Carbon::now(),
			]);

			GameSessionUser::create([
				'user_id' => $user->id,
				'session_id' => $session->id,
				'credits_before' => $user->credits
			]);
			
		}else{
			$session_user = DB::table('game_sessions_users')->where([['user_id', $user->id], ['session_id', $session->id]])->first();
			if(isset($session_user->id)){
				$session['message'] = "Вы не может играть повторно! Ваш счет: ".($session_user->score ?? 0);
				return $session;
			}
			else{
				GameSessionUser::create([
					'user_id' => $user->id,
					'session_id' => $session->id,
					'credits_before' => $user->credits
				]);
			}
		}
		$session['id'] = $session->id;
		HoldCredits::create(['user_id' => $user->id, 'hold' => (float)$game->bet, 'session_id' => $session['id']]);
		
		return $session;
	}
	
	/**
	 * @param $telegram_id, $uuid, $score
     * @return mixed
     */	
	public static function saveScoreDB($telegram_id, $uuid, $score)
    {
		$raw = DB::table('game_sessions_users')
			->leftJoin('game_sessions', 'game_sessions.id', '=', 'game_sessions_users.session_id')
			->leftJoin('users', 'users.id', '=', 'game_sessions_users.user_id')
			->select('game_sessions_users.id')
			->where([
				['users.telegram_id', $telegram_id],
				['game_sessions.uuid', "$uuid"],
			])->first();
			DB::table('game_sessions_users')->where('id', $raw->id)->update(['score' => $score]);

		$game_sessions = DB::table('game_sessions_users')
				->leftJoin('game_sessions', 'game_sessions.id', '=', 'game_sessions_users.session_id')
				->where('game_sessions.uuid', "$uuid")
				->whereNotNull('score')
				->count();
		if($game_sessions >= 2){
			return self::tsEnd($uuid);
		}else{
			return "Вы набрали $score баллов";
		}
	}
	
	/**
	 * Get bet_id and game_id by game_short_name from Games
	 * @param $game_short_name
     * @return bet_id, game_id
     */
	public static function getGameByGameShortName($game_short_name)
	{
		$game_short = explode("_", $game_short_name);
		$game_name = $game_short[0];
		$bet = (int)$game_short[1];
		$game = DB::table('game_bets')
				->leftJoin('games', 'games.id', '=', 'game_bets.game_id')
				->select('game_id', 'game_bets.id as bet_id', 'bet')
				->where([['telegam_bot_game', "$game_name"], ['bet', $bet]])
				->first();
		return $game;
	}
		
	/**
	 * Close session telegram game. Update score, winner, and credits
	 * @param $uuid
     * @return message
     */
	public static function tsEnd($uuid)
	{
		$session = DB::table('game_sessions_users')
				->leftJoin('game_sessions', 'game_sessions.id', '=', 'game_sessions_users.session_id')
				->select('game_sessions_users.user_id  as user_id', 'score', 'bet_id', 'game_sessions.id as session_id')
				->where('game_sessions.uuid', "$uuid")
				->orderBy('score', 'desc')
				->get()->toArray();
		$winner = $session[0]; $looser = $session[1];
		$bet = DB::table('game_bets')->where('id', $winner->bet_id)->value('bet'); $bet = (int)$bet;
		DB::table('game_sessions')->where('uuid', "$uuid")->update(['ended_at' => Carbon::now(), 'winner_id' => $winner->user_id]);
		self::updateCredits($winner, $bet, true);
		self::updateCredits($looser, $bet, false);
		$winner_name = DB::table('users')->where('id', $winner->user_id)->value('name');
		$mes = "Победитель: {$winner_name}, набрав {$winner->score} баллов. Счёт проигравшего: {$looser->score} баллов.";
		return $mes;
	}
			
	/**
	 * Close session telegram game. Update score, winner, and credits
	 * @param $uuid
     * @return message
     */
	public static function updateCredits($user, $bet, $winner = false)
	{
		$symbol = ($winner) ? '+' : '-';
		if($winner) $bet = $bet - Profit::setProfit($bet, $user);
		DB::table('game_sessions_users')->leftJoin('users', 'users.id', '=', 'game_sessions_users.user_id')
			->where([['session_id', $user->session_id], ['user_id', $user->user_id]])
			->update([
				'game_sessions_users.credits_after' => DB::raw("game_sessions_users.credits_before $symbol $bet"),
				'users.credits' => DB::raw("users.credits $symbol $bet")
			]);
		HoldCredits::where([['session_id', $user->session_id], ['user_id', $user->user_id]])->delete();
	}
}
