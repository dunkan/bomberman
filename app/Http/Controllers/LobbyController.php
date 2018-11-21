<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\Lobby\OpenSessionRequest;
use App\Http\Requests\Lobby\CloseSessionRequest;
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
use Session;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;
use App\Models\Tournament;
use Jenssegers\Agent\Agent;
use App\Models\UserAction;

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


    public function getGame($slug)
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
