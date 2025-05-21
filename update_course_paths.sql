-- Update Computer Science courses paths
UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/programming_materials.pdf',
    quiz_path = 'course_materials/quizzes/programming_quiz.json'
WHERE title = 'Programming';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/webdev_materials.pdf',
    quiz_path = 'course_materials/quizzes/webdev_quiz.json'
WHERE title = 'Web Development';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/java_materials.pdf',
    quiz_path = 'course_materials/quizzes/java_quiz.json'
WHERE title = 'Java';

-- Update Mathematics courses paths
UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/algebra_materials.pdf',
    quiz_path = 'course_materials/quizzes/algebra_quiz.json'
WHERE title = 'Algebra';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/calculus_materials.pdf',
    quiz_path = 'course_materials/quizzes/calculus_quiz.json'
WHERE title = 'Calculus';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/statistics_materials.pdf',
    quiz_path = 'course_materials/quizzes/statistics_quiz.json'
WHERE title = 'Statistics';

-- Update Business courses paths
UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/marketing_materials.pdf',
    quiz_path = 'course_materials/quizzes/marketing_quiz.json'
WHERE title = 'Marketing';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/finance_materials.pdf',
    quiz_path = 'course_materials/quizzes/finance_quiz.json'
WHERE title = 'Finance';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/entrepreneurship_materials.pdf',
    quiz_path = 'course_materials/quizzes/entrepreneurship_quiz.json'
WHERE title = 'Entrepreneurship';

-- Update Design courses paths
UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/uiux_materials.pdf',
    quiz_path = 'course_materials/quizzes/uiux_quiz.json'
WHERE title = 'UI/UX Design';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/graphic_design_materials.pdf',
    quiz_path = 'course_materials/quizzes/graphic_design_quiz.json'
WHERE title = 'Graphic Design';

UPDATE courses SET 
    pdf_path = 'course_materials/pdfs/motion_graphics_materials.pdf',
    quiz_path = 'course_materials/quizzes/motion_graphics_quiz.json'
WHERE title = 'Motion Graphics'; 