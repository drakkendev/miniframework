<?php

namespace Dawn\Auth;

use Dawn\Session;
use App\Models\User;
use Firebase\JWT\JWT;

class Auth
{
    protected $app;
    protected $user;
    protected $id;

    public function __construct($app = null)
    {
        $this->app = $app;

        if (Session::user() === null) {
            $this->user = null;
        } else {
            $this->user = (new User())->find(Session::user());
            $this->id = $this->user->id();
        }
    }

    public static function check($options, ...$parameters)
    {
        $auth = new static;

        foreach ($options as $option) {
            if (!$auth->$option(...$parameters)) {
                return false;
            }
        }

        return true;
    }

    public static function authenticated()
    {
        $auth = new static;

        if ($auth->user === null) {
            return false;
        }

        return true;
    }

    public static function guest()
    {
        $auth = new static;

        if ($auth->user !== null) {
            return false;
        }

        return true;
    }

    public static function owner($ownerId)
    {
        $auth = new static;

        if ($auth->id !== $ownerId) {
            return false;
        }

        return true;
    }

    public function isOwner($element)
    {
        $auth = new static;

        return $auth->id === $element->userId();
    }

    protected function authenticate($id)
    {
        Session::setUser($this->generateJWT($id));
    }

    protected function generateJWT($id)
    {
        $secret = $this->app->getKey();
        $token = array(
            "id" => $id
        );

        $jwt = JWT::encode($token, $secret);

        return $jwt;
    }

    public static function logout()
    {
        Session::destroy();
    }

    public function login($username, $password)
    {
        $this->user = new User();

        $this->user->setUsername($username);
        $this->user->setPassword($password);

        if ($id = $this->user->getColumnBy('id', 'username', $this->user->username())) {
            if (password_verify($this->user->password(), $this->user->getColumnBy('password', 'id', $id))) {
                $this->authenticate($id);
            }
        }

        return redirect();
    }

    public static function register($username, $password)
    {
        $auth = new static;
        $auth->user = new User();

        $auth->user->setUsername($username);
        $auth->user->setPassword(password_hash($password, PASSWORD_BCRYPT));

        // check if user already exists
        if (!$auth->user->getBy('username', $auth->user->username())) {
            $auth->user->create();
            $auth->authenticate($auth->user->id());
        }

        return redirect();
    }

    public static function user()
    {
        $auth = new static;

        return $auth->user;
    }

    public static function id()
    {
        $auth = new static;

        return $auth->id;
    }
}