<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Discipline;
use App\Entity\Question;
use App\Entity\Test;
use App\Repository\GradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;


#[IsGranted('ROLE_TEACHER')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(GradeRepository $gradeRepository, UserInterface $user, EntityManagerInterface $entityManager): Response
    {
        $grades = $gradeRepository->findAll();
        $tests = $entityManager->getRepository(Test::class)->findAll();

        $studentsWithGrades = [];

        // Loop through the grades to extract students with discipline, grade, and created date
        foreach ($grades as $grade) {
            $studentsWithGrades[] = [
                'student' => $grade->getUser()->getEmail(),  // Assuming User entity has getEmail() method
                'discipline' => $grade->getDiscipline()->getName(), // Assuming Discipline entity has getName() method
                'grade' => $grade->getGrade(),
                'createdAt' => $grade->getCreatedAt()->format('Y-m-d H:i:s'), // Format the date
            ];
        }

        return $this->render('admin/index.html.twig', [
            'studentsWithGrades' => $studentsWithGrades,
            'tests' => $tests,  // Pass tests to Twig template
        ]);
    }

    #[Route('/admin/export', name: 'admin_export')]
    public function export(GradeRepository $gradeRepository): BinaryFileResponse
    {
        // Get all grades
        $grades = $gradeRepository->findAll();

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Student Grades');

        // Set the headers for the columns
        $sheet->setCellValue('A1', 'Student');
        $sheet->setCellValue('B1', 'Discipline');
        $sheet->setCellValue('C1', 'Grade');
        $sheet->setCellValue('D1', 'Created At');

        // Populate rows with data
        $row = 2; // Start from the second row (after the headers)
        foreach ($grades as $grade) {
            $sheet->setCellValue('A' . $row, $grade->getUser()->getEmail());  // Assuming getEmail() method in User entity
            $sheet->setCellValue('B' . $row, $grade->getDiscipline()->getName());  // Assuming getName() method in Discipline entity
            $sheet->setCellValue('C' . $row, $grade->getGrade());
            $sheet->setCellValue('D' . $row, $grade->getCreatedAt()->format('Y-m-d H:i:s'));  // Format date
            $row++;
        }

        // Write the spreadsheet to a temporary file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'student_grades.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        // Return the file as a response for download
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
    #[Route('/test/create/manual', name: 'create_test_manual', methods: ['GET', 'POST'])]
    public function createTestManual(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            // Handle form submission
            $testTitle = $request->request->get('test_title');
            $disciplineName = $request->request->get('discipline'); // Now this is the written name of the discipline
            $timeLimit = $request->request->get('time_limit');
            $questions = $request->request->all('questions');  // Use all() to retrieve the questions array

            // Create a new discipline from the input
            $discipline = new Discipline();
            $discipline->setName($disciplineName); // Set the discipline name from user input
            $entityManager->persist($discipline); // Persist the discipline in the database

            // Create the test
            $test = new Test();
            $test->setTitle($testTitle);
            $test->setDiscipline($discipline); // Associate the new discipline with the test
            $test->setTimeLimit($timeLimit);
            $entityManager->persist($test);

            // Process each question and its answers
            $maxScore = 0;
            foreach ($questions as $questionData) {
                $question = new Question();
                $question->setText($questionData['text']);

                // Handle the multiple_correct field
                $question->setMultipleCorrect(isset($questionData['multiple_correct']));  // Checkbox might be empty, defaults to false

                $question->setTest($test);
                $question->setDiscipline($discipline);
                $entityManager->persist($question);

                // Process the answers for each question
                foreach ($questionData['answers'] as $answerData) {
                    $answer = new Answer();
                    $answer->setText($answerData['text']);
                    $answer->setIsCorrect(isset($answerData['is_correct']));  // Checkbox might be empty
                    $answer->setQuestion($question);

                    if ($answer->isCorrect()) {
                        $maxScore++;
                    }

                    $entityManager->persist($answer);
                }
            }

            // Set max score and save the test
            $test->setMaxScore($maxScore);
            $entityManager->flush();

            // Redirect or display success message
            $this->addFlash('success', 'Test created successfully!');
            return $this->redirectToRoute('admin');
        }

        return $this->render('test/create.html.twig');
    }

    #[Route('/admin/test/{id}/edit', name: 'admin_test_edit', methods: ['GET', 'POST'])]
    public function editTest(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            // Update test details
            $testTitle = $request->request->get('test_title');
            $timeLimit = $request->request->get('time_limit');
            $test->setTitle($testTitle);
            $test->setTimeLimit($timeLimit);

            // Retrieve the submitted questions array
            $questionsData = $request->request->all('questions');

            if (is_array($questionsData)) {
                foreach ($test->getQuestions() as $index => $question) {
                    if (isset($questionsData[$index])) {
                        // Update question text
                        $question->setText($questionsData[$index]['text']);

                        // Update the multipleCorrect flag
                        $question->setMultipleCorrect(isset($questionsData[$index]['multiple_correct']));

                        // Update answers for this question
                        if (isset($questionsData[$index]['answers']) && is_array($questionsData[$index]['answers'])) {
                            foreach ($question->getAnswers() as $answerIndex => $answer) {
                                if (isset($questionsData[$index]['answers'][$answerIndex])) {
                                    $answer->setText($questionsData[$index]['answers'][$answerIndex]['text']);
                                    $answer->setIsCorrect(isset($questionsData[$index]['answers'][$answerIndex]['is_correct']));
                                }
                            }
                        }
                    }
                }
            }

            // Persist changes to the database
            $entityManager->flush();

            $this->addFlash('success', 'Test, questions, and answers updated successfully!');
            return $this->redirectToRoute('admin_test_show', ['id' => $test->getId()]);
        }

        return $this->render('admin/test_edit.html.twig', [
            'test' => $test,
        ]);
    }




    #[Route('/admin/test/{id}/delete', name: 'admin_test_delete', methods: ['POST'])]
    public function deleteTest(Test $test, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($test);
        $entityManager->flush();

        $this->addFlash('success', 'Test deleted successfully!');
        return $this->redirectToRoute('admin');
    }
    #[Route('/admin/test/{id}', name: 'admin_test_show', methods: ['GET'])]
    public function showTest(Test $test): Response
    {
        return $this->render('admin/test_show.html.twig', [
            'test' => $test,
        ]);
    }
}
