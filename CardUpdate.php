<?php

//Meant to be run daily to update the database and portraits folder for card printing

namespace App\Custom;

use Storage;
use Image;

use App\Models\Courses\CourseToStudent;
use App\Models\Users\User;
use App\Models\Users\Address;
use App\Models\State;
use App\Models\Card;

class CardUpdate{
    public static function run(){

        $yesterday = time() - (60 * 60 * 24 * 1);
        $s3 = Storage::disk('s3');
        Image::configure(array('driver' => 'gd'));

        $portrait_dirs_to_process = array();    
        foreach ($s3->directories('/user_resources') as $p){
            if($s3->exists($p."/profilepic.img") || $s3->exists($p."/profilepic.jpg")){
                if($yesterday < $s3->lastModified($p."/profilepic.img")){
                    array_push($portrait_dirs_to_process, $p);
                }
            }else{
                echo $p." is either an invalid folder, or does not contain a profilepic.img\n";
            }
        }
        echo "Flagged the newest profile pics...\n";
        foreach($portrait_dirs_to_process as $p){
            echo 'Trying '.$filename."\n";
            $filename = explode("/", $p)[1].".jpg";

            if ($s3->exists('portraits/'.$filename)){
                $s3->delete('portraits/'.$filename);
            }

            if($s3->exists($p."/profilepic.img")){
                $img = $s3->get($p.'/profilepic.img');                
                $s3->put('portraits/'.$filename, (string)Image::make($img)->encode('jpg', 100));
            } 
            elseif($s3->exists($p."/profilepic.jpg")){
                $img = $s3->copy($p.'/profilepic.jpg', 'portraits/'.$filename);
            }            
            
        }

        //Copy info from all users who completed the course into the card table
        $users = array();
        $course_completions = CourseToStudent::where('status', 'passed')->whereNotNull('certificate_id')->get();
        foreach($course_completions as $c){   
           if($yesterday < strtotime($c->end_time)){
              array_push($users, User::find($c->student_id));
           }
        }
        foreach($users as $user){
            echo "Processing ".$user->id."\n";
            $card = Card::where('user_id', $user->id)->first(); //Eloquent handles the "save" vs "update" 
            if (!$card){
                $card = new Card;
                $card->created_at = $card->updated_at = date("Y-m-d h:i:sa");
                
                $temp_CourseToStudent = CourseToStudent::where('student_id', $user->id)->first();
                $temp_date = date_create($temp_CourseToStudent->end_time);
                $temp_address = Address::where('user_id', $user->id)->first();

                $card->user_id = $user->id;
                $card->language = 'English';
                $card->first_name = ucfirst($user->first_name);
                $card->last_name = ucfirst($user->last_name);
                $card->street = ucwords($temp_address->street);
                $card->apt = ucwords($temp_address->apt);
                $card->city = ucwords($temp_address->city);
                $card->state_abbrv = State::find($temp_address->state)->abbrv;
                $card->zip = $temp_address->zip;

                //set completed and expiration dates                
                $card->completed_date = date_format($temp_date, "m/d/Y");

                //exp date is 3 years from completed date
                date_add($temp_date, date_interval_create_from_date_string('3 years'));
                $card->expiration_date = date_format($temp_date, "m/d/Y");

                $card->certification_number = $temp_CourseToStudent->certificate_id;
                $card->instructor = 'John Smith';
                $card->program_manager = 'John Smith';
                
                $card->save();//Eloquent handles the "save" vs "update" 
            }
        }
        return true;
    }    
}