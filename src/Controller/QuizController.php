<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Grade;
use App\Repository\DisciplineRepository;
use App\Repository\GradeRepository;
use App\Repository\QuestionRepository;
use App\Repository\TestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class QuizController extends AbstractController
{
    #[Route('/quiz/', name: 'quiz')]
    public function index(
        Request                $request,
        SessionInterface       $session,
        QuestionRepository     $questionRepository,
        UserInterface          $user,
        EntityManagerInterface $entityManager,
        DisciplineRepository   $disciplineRepository,
        GradeRepository        $gradeRepository,
        TestRepository         $testRepository
    ): Response {
        // Initialize variables
        $disciplines = $disciplineRepository->findAll();
        $disciplineId = $request->query->get('discipline_id');
        $questions = [];
        $discipline = null;
        $results = [];
        $gradeValue = null;
        $test = null;
        $timeLimit = null;

        // Check if discipline ID is provided
        if ($disciplineId) {
            $discipline = $disciplineRepository->find($disciplineId);
            if (!$discipline) {
                throw $this->createNotFoundException('Discipline not found.');
            }

            // Store discipline ID in the session
            $session->set('discipline_id', $disciplineId);

            // Retrieve the corresponding test and its time limit
            $test = $testRepository->findOneBy(['discipline' => $discipline]);
            $timeLimit = $test ? $test->getTimeLimit() : null;

            // Fetch the tests and questions related to the selected discipline
            $tests = $testRepository->findBy(['discipline' => $discipline]);
            $questions = $questionRepository->findBy(['discipline' => $discipline]);
            $allTests = $testRepository->findAll();

            // Ensure session is started
            if (!$session->isStarted()) {
                $session->start();
            }

            // Generate a new salt only on GET request
            if ($request->isMethod('GET')) {
                $salt = $this->generateSalt();
                $session->set('salt', $salt);  // Store the salt in the session
            } else {
                // On POST requests, retrieve the existing salt from the session
                $salt = $session->get('salt');
            }

            // Shuffle questions and answers with the salt on GET or POST request
            if (!empty($questions)) {
                $shuffledQuestions = $this->shuffleQuestionsAndAnswers($questions, $salt);  // Pass the salt
                $session->set('shuffled_questions', $shuffledQuestions);
            }
        }

        // Handle POST request (i.e., when the test is submitted)
        if ($request->isMethod('POST')) {
            $this->handleTestSubmission($request, $session, $disciplineRepository, $entityManager, $results, $gradeValue, $user);
        }

        // Render the quiz page with the necessary data
        return $this->render('quiz/index.html.twig', [
            'disciplines' => $disciplines ?? null,
            'questions' => $session->get('shuffled_questions', []),
            'results' => $results ?? [],
            'grade' => $gradeValue ?? null,
            'discipline' => $discipline ?? null,
            'timeLimit' => $timeLimit ?? null,
            'tests' => $tests ?? [],
            'alltests' => $allTests ?? [],
        ]);
    }

    // Helper function to generate a random salt
    private function generateSalt(): string
    {
        return bin2hex(random_bytes(8));  // Generate a random 16-character salt
    }

    // Helper function to shuffle questions and answers, and hash the GUIDs with salt
// Helper function to shuffle questions and answers, and hash the GUIDs with salt
    private function shuffleQuestionsAndAnswers(array $questions, string $salt): array
    {
        $shuffledQuestions = [];

        // Shuffle the questions first
        shuffle($questions);

        foreach ($questions as $question) {
            // Hash the question GUID with the salt
            $questionHash = hash('sha256', $salt . $question->getGuid()->toString());

            // Get answers for the question and shuffle them
            $answersArray = $question->getAnswers()->toArray();
            shuffle($answersArray);  // Shuffle the answers

            // Hash each answer GUID with the salt
            foreach ($answersArray as $answer) {
                $answer->hashedGuid = hash('sha256', $salt . $answer->getGuid()->toString());
            }

            // Store the shuffled question and its shuffled answers
            $shuffledQuestions[] = [
                'question' => $question,
                'questionHash' => $questionHash,  // Include hashed question GUID with salt
                'shuffledAnswers' => $answersArray,  // Include shuffled answers with hashed GUIDs
            ];
        }

        return $shuffledQuestions;
    }



    // Helper function to handle test submission
    private function handleTestSubmission(
        Request $request,
        SessionInterface $session,
        DisciplineRepository $disciplineRepository,
        EntityManagerInterface $entityManager,
        array &$results,
        &$gradeValue,
        UserInterface $user
    ) {
        // Retrieve discipline_id from the session
        $disciplineId = $session->get('discipline_id');
        $discipline = $disciplineRepository->find($disciplineId);
        if (!$discipline) {
            throw $this->createNotFoundException('Discipline not found.');
        }

        // Retrieve the salt from the session (do not generate a new one)
        $salt = $session->get('salt');

        // Get user answers and shuffled questions
        $userAnswers = $request->request->all();
        $shuffledQuestions = $session->get('shuffled_questions', []);

        // Create a reverse lookup table for hashed GUIDs
        $guidMapping = [];
        foreach ($shuffledQuestions as $shuffledQuestion) {
            $question = $shuffledQuestion['question'];
            $questionGuid = $question->getGuid()->toString();
            $questionHash = hash('sha256', $salt . $questionGuid);  // Hash the question GUID with the salt

            $guidMapping[$questionHash] = $questionGuid;  // Map hash to original GUID

            foreach ($shuffledQuestion['shuffledAnswers'] as $answer) {
                $answerGuid = $answer->getGuid()->toString();
                $answerHash = hash('sha256', $salt . $answerGuid);  // Hash the answer GUID with the salt

                $guidMapping[$answerHash] = $answerGuid;  // Map hash to original GUID
            }
        }

        // Process answers based on hashed GUIDs
        $score = 0;
        $maxScore = count($shuffledQuestions);
        foreach ($shuffledQuestions as $shuffledQuestion) {
            $question = $shuffledQuestion['question'];
            $questionHash = hash('sha256', $salt . $question->getGuid()->toString());  // Re-hash the question GUID
            $selectedAnswersHashes = $userAnswers['question_' . $questionHash] ?? [];

            if (!is_array($selectedAnswersHashes)) {
                $selectedAnswersHashes = [$selectedAnswersHashes];  // Ensure it's an array
            }

            // Map the hashed GUIDs back to the original GUIDs
            $selectedAnswersGuids = array_map(fn($hash) => $guidMapping[$hash], $selectedAnswersHashes);

            // Retrieve selected answers based on original GUIDs
            $selectedAnswers = [];
            foreach ($selectedAnswersGuids as $guid) {
                $answer = $entityManager->getRepository(Answer::class)->findOneBy(['guid' => $guid]);
                if ($answer) {
                    $selectedAnswers[] = $answer;
                }
            }

            // Evaluate correctness of selected answers
            $correctAnswers = array_filter($question->getAnswers()->toArray(), fn($answer) => $answer->isCorrect());
            $correctIds = array_map(fn($answer) => $answer->getId(), $correctAnswers);
            $selectedCorrectIds = array_intersect(array_map(fn($answer) => $answer->getId(), $selectedAnswers), $correctIds);
            $selectedIncorrectIds = array_diff(array_map(fn($answer) => $answer->getId(), $selectedAnswers), $correctIds);

            // Calculate question score
            $allowsMultipleCorrect = count($correctIds) > 1;
            $questionScore = $this->calculateQuestionScore($allowsMultipleCorrect, $selectedCorrectIds, $selectedIncorrectIds);
            $score += $questionScore;

            // Prepare feedback for results
            $correctAnswersTexts = implode(', ', array_map(fn($answer) => $answer->getText(), $correctAnswers));
            $selectedAnswersTexts = implode(', ', array_map(fn($answer) => $answer->getText(), $selectedAnswers));

            $results[] = [
                'question' => $question->getText(),
                'userAnswerTexts' => $selectedAnswersTexts ?: 'No answer',
                'isCorrect' => count($selectedCorrectIds) == count($correctIds) && empty($selectedIncorrectIds),
                'correctAnswerTexts' => $correctAnswersTexts,
            ];
        }

        // Calculate grade based on score percentage
        $percentage = ($score / $maxScore) * 100;
        $gradeValue = $this->convertScoreToGrade($percentage);

        // Save the grade to the database
        $this->saveGrade($gradeValue, $user, $discipline, $entityManager);

        // Clear the session after test completion
        $session->remove('shuffled_questions');
        $session->remove('test_start_time');
    }

    // Helper function to calculate the score for a question
    private function calculateQuestionScore(bool $allowsMultipleCorrect, array $selectedCorrectIds, array $selectedIncorrectIds): int
    {
        $questionScore = 0;
        if ($allowsMultipleCorrect) {
            $questionScore += 0.5 * count($selectedCorrectIds);
            $questionScore -= 0.5 * count($selectedIncorrectIds);
            $questionScore = max($questionScore, 0);
        } else {
            if (count($selectedCorrectIds) == 1 && empty($selectedIncorrectIds)) {
                $questionScore = 1;
            }
        }
        return $questionScore;
    }

    // Helper function to save the grade
    private function saveGrade(int $gradeValue, UserInterface $user, $discipline, EntityManagerInterface $entityManager)
    {
        $grade = new Grade();
        $grade->setGrade($gradeValue);
        $grade->setUser($user);
        $grade->setDiscipline($discipline);
        $grade->setCreatedAt(new \DateTime());

        $entityManager->persist($grade);
        $entityManager->flush();
    }

    // Helper function to convert score percentage to grade
    private function convertScoreToGrade(float|int $percentage): int
    {
        if ($percentage >= 90) return 6;
        if ($percentage >= 75) return 5;
        if ($percentage >= 60) return 4;
        if ($percentage >= 50) return 3;
        return 2;
    }
}
