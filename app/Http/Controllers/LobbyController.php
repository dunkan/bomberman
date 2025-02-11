<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\Lobby\OpenSessionRequest;
use App\Http\Requests\Lobby\CloseSessionRequest;
use App\Http\Resources\UserResource;
use App\Models\GameBet;
use App\Models\Game;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\GameSession;
use App\Models\GameSessionUser;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Gate;
use Auth;
use App\Events\PrivateNotifyEvent;
use Mail;
use Session;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;
use App\Models\Tournament;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;
use App\Models\UserAction;
use         Pusher\Pusher;

class LobbyController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $games = Game::all();

        return view('lobby.index', compact('games'));
    }

    public function getNewUser()
    {
        $temp = Route::getCurrentRoute()->parameters();

        $uuid = Input::get('ref');
        \Session::put('uuid', $uuid);
        $game_id = Input::get('game');
        $game = Game::find($game_id);
        return redirect("/game/".$game->slug);
    }


    public function getChallenge()
    {
        $games = Game::all();
        $challenge = GameSessionUser::where('user_id', Auth::user()->id)->where('credits_after', null)->get();
        $user_id = Auth::user()->id;
        $sessions = GameSession::with('users_sessions')->whereHas('users_sessions', function ($query) use ($user_id){
            $query->where('user_id', $user_id);
        })->get();
        return view('lobby.challenge', compact('games', 'challenge', 'sessions'));
    }



    public function getGamePlayFromChallenge(Request $request)
    {
        return response()->json(['data' => env('GAME_HOST')."/?$uuid/".Auth::user()->id]);
    }


    public function checkGameSession(Request $request)
    {
        $user_game_session = GameSessionUser::where('user_id', Auth::user()->id)->pluck('session_id')->all();
        $friend_id = $request->friend_id;

        $games = GameSession::whereIn('id', $user_game_session)->where('winner_id', null)->whereHas('users_sessions', function ($query) use ($friend_id) {
            $query->where('user_id', $friend_id);
        })->first();


        $checkGames = GameSession::whereIn('id', $user_game_session)->where('winner_id', null)->whereHas('users_sessions', function ($query) use ($friend_id) {
            $query->where('user_id', Auth::user()->id)->where('score', '!=', null);
        })->first();

        if ($checkGames != null)
        {
            $error = true;
        } else {
            $error = false;
        }

        if ($games == null)
        {
            return response()->json(['data' => 0, 'error' =>  $error]);
        } else {
            return response()->json(['data' => $games->id, 'error' =>  $error]);
        }


    }


    public function getGamePlay(Request $request)
    {
        $message = "Игрок ".Auth::user()->name.' вызвал вас на дуэль';
        event(new PrivateNotifyEvent(Auth::user(), $message, $request->friend_id));
        if ($request->session_id == 0)
        {
            $uuid = str_random(8);
            if (GameSession::where('uuid', $uuid)->first() != null) {
                while (GameSession::where('uuid', $uuid)->first() != null) {
                    $uuid = str_random(8);
                }
            }

                $gameSession = new GameSession();
                $gameSession->bet_id = $request->bet_id;
                $gameSession->game_id = $request->game_id;
                $gameSession->started_at = Carbon::now();
                $gameSession->uuid = $uuid;
                $gameSession->save();

                $gameSessionUser = new GameSessionUser();
                $gameSessionUser->user_id = $request->friend_id;
                $gameSessionUser->session_id = $gameSession->id;
                $gameSessionUser->credits_before = User::find($request->friend_id)->credits;
                $gameSessionUser->save();

                $gameSessionUser = new GameSessionUser();
                $gameSessionUser->user_id = Auth::user()->id;
                $gameSessionUser->session_id = $gameSession->id;
                $gameSessionUser->credits_before = Auth::user()->credits;
                $gameSessionUser->save();

            //broadcast(new PrivateNotifyEvent(Auth::user(), "Пользователь ".Auth::user()->name.'. вызвал вас на бой', $request->friend_id));
            return response()->json(['data' => env('GAME_HOST')."/?$uuid/".Auth::user()->id]);
        } else {
            $uuid = GameSession::find($request->session_id)->uuid;
            return response()->json(['data' => env('GAME_HOST')."/?$uuid/".Auth::user()->id]);
        }

    }


    public function saveScore(Request $request)
    {
        $score = (int)$request->score;
        $gameSession = GameSession::where('uuid', $request->uuid)->first();
        $gameSessionUserCheck = GameSessionUser::where(['session_id' => $gameSession->id])->where('user_id', '!=', $request->user_id)->first();
        $gameSessionUser = GameSessionUser::where(['session_id' => $gameSession->id, 'user_id' => $request->user_id])->first();


        if ($gameSessionUserCheck->score != null)
        {
            if ($gameSessionUserCheck->score > $score)
            {
                $gameSession->winner_id = $gameSessionUserCheck->user_id;
                $gameSessionUserCheck->credits_after = $gameSessionUserCheck->credits_after + $gameSession->bet->bet;
                $gameSessionUser->score = (int)$request->score;
                $winner = User::find($gameSessionUserCheck->user_id);
                $winner->credits = $winner->credits + $gameSession->bet->bet;
                $winner->update();
                $looser = User::find($gameSessionUser->user_id);
                $looser->credits = $looser->credits - $gameSession->bet->bet;
                $looser->update();

                $gameSessionUserCheck->credits_after = $winner->credits;
                $gameSessionUserCheck->update();

                $gameSessionUser->credits_after = $looser->credits;
                $gameSessionUser->update();
            } else {
                $gameSession->winner_id = $gameSessionUser->user_id;
                $gameSessionUser->credits_after = $gameSessionUser->credits_after + $gameSession->bet->bet;
                $gameSessionUser->score = (int)$request->score;
                $winner = User::find($gameSessionUser->user_id);
                $winner->credits = $winner->credits + $gameSession->bet->bet;
                $winner->update();
                $looser = User::find($gameSessionUserCheck->user_id);
                $looser->credits = $looser->credits - $gameSession->bet->bet;
                $looser->update();

                $gameSessionUser->credits_after = $winner->credits;
                $gameSessionUser->update();

                $gameSessionUserCheck->credits_after = $looser->credits;
                $gameSessionUserCheck->update();
            }
            $gameSession->ended_at = Carbon::now();
            $gameSession->update();
        } else {
            $gameSessionUser->score = $score;
            $gameSessionUser->update();
        }

        return response()->json(['data' => env('APP_URL')]);
    }


    public function getLobby()
    {
        return view('lobby.lobby');
    }


    public function getUsers(Request $request)
    {

        return  UserResource::collection(User::where('id', '!=', Auth::user()->id)->get());
    }

    public function getCurrentUsers(Request $request)
    {
        return  Auth::user() == null ? "" : Auth::user()->uuid;
    }



    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getGames()
    {

        return view('lobby.games');
    }


    public function statistic()
    {
        return view('lobby.statistic')->with(['actions' => \App\Models\UserAction::all(), 'users' => \App\Models\User::all()]);
    }



    public function getGame($slug, Request $request)
    {
        $game = Game::where('slug', $slug)->first();
        $user = Auth::user();
        if ($user) {
            $action = UserAction::where(['user_id' => $user->id, 'game_id' => $game->id, 'action' => UserAction::VIEW ])->first();
            if ($action == null) {
                $action = new UserAction();
                $action->user_id = $user->id;
                $action->game_id = $game->id;
                $action->action = UserAction::VIEW;
                $action->amount = 1;
                $action->save();
            } else {
                $action->amount = $action->amount + 1;
                $action->update();
            }
        }
        $games = Game::all();



        $uuid = $request->ref;
        if ($uuid != null)
        {
            \Session::put('uuid', $uuid);

            $game = Game::find($request->game);
            \Session::put('game_id', $game->id);

            return view('lobby.game', compact('game', 'games'));
        }



        return view('lobby.game', compact('game', 'games'));
    }


    public function invitation()
    {
        $user = Auth::user();
        if ($user->email == null) {
           do {
                $user->name = "gamer-". rand(100, 1000);
            } while (null != User::where('name', $user->name)->first());
            $password = str_random(8);
            $user->password = Hash::make($password);
            $user->update();
            $new_user = true;
            $new_user_name = $user->name;
        } else {
            $new_user = false;
        }
        
        $games = Game::all();
        $games = Game::all();
        $agent = new Agent();
        if ($agent->isMobile() || $agent->isTablet()){
            return redirect('/');
        } else {
            return view('lobby.invitation', compact('user', 'games', 'new_user', 'password', 'new_user_name'));
        }
    }


    public function profileSave(Request $request)
    {
        if ($request->email == null || $request->email == "")
        {
            return back()->with('error', 'Поле email не может быть пустым');
        }

        $user = User::find($request->id);
        $user->fill($request->all());
        $user->update();
        return back()->with('success', 'Данные сохранены');
    }



    public function profile()
    {
        $games = Game::all();
        return view('lobby.profile', compact('games'));
    }


    public function gameHistory()
    {
        $users_sessions = GameSessionUser::where(['user_id' => Auth::id()])->get();
        $sessions = GameSessionUser::get();
        $games = Game::all();
        return view('lobby.history', compact('sessions', 'games', 'users_sessions'));
    }

    public function tournaments()
    {
        $bets = GameBet::with(['game'])
            ->get()
            ->all();
        $games = Game::all();
        $participants = Tournament::get();
        return view('lobby.tournaments', compact('bets', 'participants', 'games'));
    }

    public function removeUserFromSession()
    {

    }


}
