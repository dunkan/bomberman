<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 *
 * @package App
 * @property string $title
*/
class Role extends Model
{
    protected $fillable = ['title'];
    protected $hidden = [];


    CONST ADMIN = 1;
    CONST GAMER = 2;
    
    
}
