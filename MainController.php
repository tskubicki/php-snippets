<?php

namespace App\Http\Controllers;

use Storage;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Requests;


class MainController extends Controller
{
    public function index(){
    	$posts = Post::all()->sortByDesc("id");

    	return view('home', ['posts' => $posts]);
    }

    public function test(){
    	$posts = Post::all();

    	return view('test', ['posts' => $posts]);
    }

    public function storeMessage(Request $request)
    {        
        if($request->input('name') && $request->input('relationship') && $request->input('message')){
            $post = new Post;

            $post->name = $request->input('name');
            $post->relationship = $request->input('relationship');
            $post->message = $request->input('message');
            $post->save();
        }
        
        return redirect('/');
    }

    public function storeImage(Request $request)
    {
        if($request->input('name') && $request->input('relationship') && $request->file('image')){
            $post = new Post;

            $s3 = Storage::disk('s3');
            $random_string = uniqid();
            $uploadedFile = $request->file('image');
            $fileName = $uploadedFile->getClientOriginalName(); 

            $s3->put('assets/images/'.$random_string.$fileName, file_get_contents($uploadedFile));
            $post->name = $request->input('name');
            $post->relationship = $request->input('relationship');
            $post->imageurl = $s3->url('assets/images/'.$random_string.$fileName);
            $post->save();
        }
        
        return redirect('/');
    }

    public function storeVideo(Request $request)
    {
        if($request->input('name') && $request->input('relationship') && ($request->input('videourl') xor $request->file('videoupload'))){
            $post = new Post;

            $post->name = $request->input('name');
            $post->relationship = $request->input('relationship');
            if($request->input('videourl')){
                $post->videourl = $request->input('videourl');  
                $post->save();   
            }
            if($request->file('videoupload')){
                $random_string = uniqid();
                $uploadedFile = $request->file('videoupload');
                $fileName = $uploadedFile->getClientOriginalName(); 

                if(strtolower(substr($fileName, -3)) == "mp4"){ 
                    $s3 = Storage::disk('s3');
                    $s3->put('assets/videos/'.$random_string.$fileName, file_get_contents($uploadedFile));
                    $post->videoupload = $s3->url('assets/videos/'.$random_string.$fileName);
                    $post->save();
                }     
            }
        }
        
        return redirect('/');
    }
}
