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
use DB;
use Illuminate\Support\Facades\Gate;
use Auth;
use Session;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use RuntimeException;
use App\Models\Tournament;

class LobbyController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        //return new UserResource(User::all());
        $games = Game::all();

        $user = \Auth::user();
        if ($user != null)
        {
            $encodedChatId = '42bgw';
            $siteDomain = 'gamechainger.io';
            $siteUserExternalId = $user->id;
            $siteUserFullName = substr(Auth::user()->email, 0, strrpos(Auth::user()->email, '@'));
            $secretKey = env("CHAT_KEY");

            $signatureDataParts = $siteDomain.$siteUserExternalId.$siteUserFullName.$secretKey;
            $hash = md5($signatureDataParts);
            $name = substr(Auth::user()->email, 0, strrpos(Auth::user()->email, '@'));
        } else {
            $hash = null;
            $name = null;
        }

        return view('lobby.index', compact('games', 'hash', 'name'));
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
        
        return view('lobby.invitation', compact('user', 'games', 'new_user', 'password', 'new_user_name'));
    }



    public function gameHistory()
    {
        if (! Gate::allows('session_access')) {
            return abort(401);
        }

        $users_sessions = GameSessionUser::where(['user_id' => Auth::id()])
            ->get();

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
