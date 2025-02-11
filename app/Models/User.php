<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword;
use Hash;
use Auth;
use DB;

/**
 * Class User
 *
 * @package App
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $remember_token
*/
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'token',
        'credits',
        'password',
        'remember_token',
        'role_id',
        'first_name',
        'last_name',
        'country',
        'uuid'
    ];

    protected $hidden = ['password', 'remember_token'];
    
    
    CONST ADMIN = 1;
    CONST GAMER = 2;

    CONST REGISTERED = true;
    CONST NEWUSER = false;


    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function payments()
    {
        return $this->hasMany(PaymentHistory::class);
    }
	
	public function holds()
    {
        return $this->hasMany(HoldCredits::class, 'user_id', 'id');
    }

    /**
     * Hash password
     * @param $input
     */
    public function setPasswordAttribute($input)
    {
        if ($input)
            $this->attributes['password'] = app('hash')->needsRehash($input) ? Hash::make($input) : $input;
    }
    

    /**
     * Set to null if empty
     * @param $input
     */
    public function setRoleIdAttribute($input)
    {
        $this->attributes['role_id'] = $input ? $input : null;
    }
    
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    /**
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
       $this->notify(new ResetPassword($token));
    }

    /**
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        $model->token = self::getUniqueHash();

        $model->save();

        return $model;
    }

    /**
     * @return string
     */
    public static function getUniqueHash()
    {
        $hash = static::getUniqueString(20);
        if(User::where('token', '=', $hash)->get()->first()){
            return self::getUniqueHash();
        }else{
            return $hash;
        }
    }

    /**
     * @param $length
     * @return string
     */
    public static function getUniqueString($length)
    {
        return str_random($length);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $role
     * @return bool
     */
    public function is($role)
    {
        $role = strtolower($role);

        switch ($role){
            case 'admin':
                return $this->role_id == self::ADMIN;
            case 'gamer':
                return $this->role_id == self::GAMER;
            default:
                return false;
        }
    }

    /**
     * @return string
     */
    public function getSlugRole()
    {
        switch ($this->role_id){
            case self::ADMIN:
                return 'admin';
            case self::GAMER:
                return 'gamer';
            default:
                return '';
        }
    }

    /**
     * @return string
     */
    public static function createNewUserByToken($token)
    {
        if ($token == null) {
            return;
        }

        $user = new User();
        $user->token = $token;
        $user->credits = 0;
        $user->password = bcrypt(str_random(5));
        $user->role_id = User::GAMER;
        $user->save();

        Auth::loginUsingId($user->id);
        //Auth::attempt(['' => $user->email, 'password' => $pass]);

        //Auth::login($user);
        return $user;
    }


    public function profile()
    {
        return $this->hasOne('App\SocialProfile');
    }
	
	/**
     * @return string
     */
    public static function getCredits($user_id)
    {
		return self::select(DB::raw('credits - COALESCE((Select SUM(hold) From hold_credits Where user_id = '.$user_id.'), 0) as balance'))->where('id', $user_id)->value('balance');
	}
	
}
