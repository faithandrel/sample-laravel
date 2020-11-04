<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Socialite;
use JWTAuth;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use App\Repositories\UserRepository;

class AccessController extends Controller
{
	private $userRepository;

	public function __construct(UserRepository $userRepo)
    {
    	$this->userRepository = $userRepo;
    }

	public function login(Request $request) 
	{
	    if (empty($request->facebook)) {
	        return response()->json(['message' => 'facebook unavailable'], 
	                                HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
	    }

	    $user = User::where('facebook', '=', $request->facebook)->first();

	    if (is_null($user)) {
	    	$data = $request->all();
	    	$user = $this->signup($data);
	        
	        if(is_null($user)) {
	        	return response()->json(['message' => 'user unavailable'], 
	                                HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
	        }
	    }

	    $username = $user->name ? $user->name : '';
	    $customClaims = ['id'   => $user->id,
	                     'name' => $username ];
	   
	    if ( ! $token = JWTAuth::fromUser($user, $customClaims)) {
	        return response()->json(['message' => 'token unavailable'], 
	                                HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
	    }
	    
	    if(!empty($deviceToken = $request->deviceToken)) {
	      $user->device_token = $deviceToken;
	      $user->save();
	    }

	    header('Authorization: Bearer ' . $token);
	    return response()->json(['name' => $username]);
	}

	public function signup($data)
	{
	    if(!empty($data['facebook'])) {
	      $facebook_user = Socialite::driver('facebook')->userFromToken($data['access']);
	    
	      if(!empty($facebook_user)) {
	          $user = new User;
	          $user->password = bcrypt(str_random(12));
	          $user->facebook = $data['facebook'];
	          $user->email = $facebook_user->getEmail();
	          $user->save();

	          return $user;
	      }
	    }
	    return null;
	}

	public function saveUsername(SignUpRequest $request) 
	{
		$user = User::find($request->user);

		$user->name = $request->username;
		$user->save();

		return response()->json(['username' => $user->name]);
	}

	public function bio(Request $request)
	{
		$userId = $request->get('user');
		$user = $this->userRepository->find($userId);

		return response()->json(['bio' => $user->description, 'name' => $user->name]);
	}

    public function logout(Request $request) 
    {
    	$user = auth()->user();
    	$user->device_token = null;
    	$user->save();

        return response()->json([]);
    }
}

/**
 * The app uses facebook login for login/signup. 
 * In the login function if the user's facebook id is not found we create a new user. 
 * Otherwise, it proceeds to logging the user in and sending a JWT token 
 * for the mobile app.
 * This allows for a seamless signup process for users
 * 
 * Next, please see the User Model - app/Models/User.php
 */
