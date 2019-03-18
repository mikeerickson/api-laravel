<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Faker\Factory as Faker;

class APIToken extends Model
{
    protected $table    = 'api_tokens';
    protected $guarded  = ['id'];
    protected $fillable = ['user_name', 'email', 'token', 'active', 'expires'];

    public function setTokenAttribute($value)
    {
        if ($value === '') {
            $this->attributes['token'] = $this->createToken();
        }
    }

    public function createToken()
    {
        return Faker::create()->uuid;
    }

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->attributes['token'] = $model->createToken();
            $user = User::where('email', $model->attributes['email'])->first();
            if ($user) {
                $model->attributes['user_id'] = $user->id;
            }
        });

        static::updating(function ($model) {
            $model->attributes['token'] = $model->createToken();
        });
    }
}
