<?php

namespace App\DataFixtures;

use App\Entity\Discipline;
use App\Entity\Test;
use App\Entity\Question;
use App\Entity\Answer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a Discipline
        $discipline = new Discipline();
        $discipline->setName('Geografiq2');
        $manager->persist($discipline);

        // Create a Test
        $test = new Test();
        $test->setTitle('Sample Test Geografiq2');
        $test->setDiscipline($discipline);
        $test->setTimeLimit(1); //
        $manager->persist($test);

        // Create and add questions
        $questions = []; // To keep track of added questions

        $question1 = new Question();

        $question1->setText('What is the capital of France?');
        $question1->setMultipleCorrect(true);
        $question1->setTest($test);
        $question1->setDiscipline($discipline); // Associate Question with Discipline
        $manager->persist($question1);
        $questions[] = $question1; // Add to the list of questions

        $answer1 = new Answer();
        $answer1->setText('DA');
        $answer1->setIsCorrect(true);
        $answer1->setQuestion($question1);
        $manager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setText('London');
        $answer2->setIsCorrect(false);
        $answer2->setQuestion($question1);
        $manager->persist($answer2);

        $answer3 = new Answer();
        $answer3->setText('DA');
        $answer3->setIsCorrect(true);
        $answer3->setQuestion($question1);
        $manager->persist($answer3);

        $question2 = new Question();
        $question2->setText('What is 2 + 2?');
        $question2->setMultipleCorrect(false);
        $question2->setTest($test);
        $question2->setDiscipline($discipline); // Associate Question with Discipline
        $manager->persist($question2);
        $questions[] = $question2; // Add to the list of questions

        $answer1 = new Answer();
        $answer1->setText('4');
        $answer1->setIsCorrect(true);
        $answer1->setQuestion($question2);
        $manager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setText('5');
        $answer2->setIsCorrect(false);
        $answer2->setQuestion($question2);
        $manager->persist($answer2);

        // Dynamically calculate max score based on the number of questions
        $maxScore = count($questions);
        $test->setMaxScore($maxScore);  // Set max score as the number of questions

        $manager->flush();
    }
}
