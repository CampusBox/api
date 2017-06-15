<?php



namespace App;

use App\Student;
use League\Fractal;

class StudentSearchTransformer extends Fractal\TransformerAbstract
{
  private $params = [];

  function __construct($params = []) {
    $this->params = $params;
  }
  protected $availableIncludes = [
  'Skills',
  'SocialAccounts',
  'Following',
  'Follower'
  ];
  protected $defaultIncludes = [
  'Follower',
  'Following',
  'Skills',
  'SocialAccounts',
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
    return [
            "username" => (string)$student->username?: 0 ,
            "title" => (string)$student->name?: null,
            "about" => (string)$student->about?: null,
            "photo" => (string)$student->image?: null,
            "college" => [
                "name" => (string)$student->College['name']?: null,
            ],
            "following" => $this->params['value1']
    
    ];
  }
  public function includeSkills(Student $student) {
    $skills = $student->Skills;

    return $this->collection($skills, new StudentSkillTransformer);
  }
  public function includeSocialAccounts(Student $student) {
        $socials = $student->SocialAccounts;

        return $this->collection($socials, new SocialTransformer);
    }
}
