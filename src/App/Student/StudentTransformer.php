<?php



namespace App;

use App\Student;
use League\Fractal;
use DateTime;

class StudentTransformer extends Fractal\TransformerAbstract
{
  private $params = [];

  function __construct($params = []) {
    $this->params = $params;
  }
  protected $availableIncludes = [
  'Events',
  'Skills',
  'SocialAccounts',
  'Following',
  'BookmarkedContents',
  'Follower'
  ];
  protected $defaultIncludes = [
  'Follower',
  'Following',
  'Events',
  'Skills',
  'SocialAccounts',
  'BookmarkedContents',
  'AttendingEvents',
  'CreativeContents'
  ];
  public function transform(Student $student)
  {
    $this->params['value1'] = false;
    if(isset($this->params['type']) && $this->params['type'] == 'get'){
     $following = $student->Follower;
     for ($i=0; $i < count($student->Follower); $i++) { 
       if($student->Follower[$i]->username == $this->params['username']){
         $this->params['value1'] = true;
         break;
       }
     }
   } 

   if ($student->birthday != "0000-00-00 00:00:00") {
    $birthday = new DateTime($student->birthday);
    $this->params['birthday'] = $birthday->format('F j, Y');
  } else {
    $this->params['birthday'] = "Not set";
  }

  if($student->stamp == null){
    $this->params['stamp'] = new DateTime();
  } else{
    $this->params['stamp'] = $student->stamp;
  }

  return [
  "username" => (string)$student->username?: 0 ,
  "name" => (string)$student->name?: null,
  "subtitle" => (string)$student->about?: null,
  "photo" => (string)$student->image?: null,

  "contacts" => [
  "email" => (string)$student->email?: null,  
  "phone" => (integer)$student->phone?: null,
  ],
  "about" => [
  "age" => (integer)$student->age?: null,
  "gender" => (string)$student->gender?: null,
  "home_city" => (string)$student->home_city?: null,
  "created_on" => $this->params['stamp']->format('F j, Y'),
  "birthday" => $this->params['birthday'],
  ],

  "studies" => [
  "grad_id" => (integer)$student->grad_id?: null,
  "branch_id" => (integer)$student->branch_id?: null,
  "year" => (string)$student->year?: null,
  "class_id" => (integer)$student->class_id?: null,
  "passout_year" => (integer)$student->passout_year?: null,
  ],
  "following" => $this->params['value1']

  ];
}
public function includeEvents(Student $student) {
  $events = $student->Owner;

  return $this->Collection($events, new EventMiniTransformer);
}
public function includeCollege(Student $student) {
  $college = $student->Owner;

  return $this->Collection($college, new CollegeTransformer);
}
public function includeBookmarkedContents(Student $student) {
  $contents = $student->BookmarkedContents;

  return $this->collection($contents, new ContentMiniTransformer);
}
public function includeCreativeContents(Student $student) {
  $contents = $student->CreativeContents;

  return $this->collection($contents, new ContentMiniTransformer);
}
public function includeAttendingEvents(Student $student) {
  $events = $student->AttendingEvents;

  return $this->collection($events, new EventTransformer);
}
public function includeSkills(Student $student) {
  $skills = $student->Skills;

  return $this->collection($skills, new StudentSkillTransformer);
}
public function includeSocialAccounts(Student $student) {
  $socials = $student->SocialAccounts;

  return $this->collection($socials, new SocialTransformer);
}
public function includeFollowing(Student $student) {
  $followers = $student->Following;

    return $this->collection($followers, new StudentMiniTransformer(['username' => $this->params['username'], 'type' => 'get']));
  }
  public function includeFollower(Student $student) {
    $followers = $student->Follower;

    return $this->collection($followers, new StudentMiniTransformer(['username' => $this->params['username'], 'type' => 'get']));
  }
}