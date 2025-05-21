<?php
require_once('tcpdf/tcpdf.php');

// Course materials content
$courseMaterials = [
    'Programming' => [
        'title' => 'Introduction to Programming',
        'sections' => [
            [
                'title' => '1. Introduction to Programming Concepts',
                'content' => "Programming is the process of creating a set of instructions that tell a computer how to perform a task. Programming can be done using a variety of computer programming languages.\n\nKey concepts covered in this section:\n- Variables and Data Types\n- Control Structures\n- Functions and Methods\n- Basic Algorithms"
            ],
            [
                'title' => '2. Variables and Data Types',
                'content' => "Variables are containers for storing data values. In programming, we use different data types to store different kinds of information.\n\nCommon data types include:\n- Integers (whole numbers)\n- Floating-point numbers (decimals)\n- Strings (text)\n- Booleans (true/false)"
            ],
            [
                'title' => '3. Control Structures',
                'content' => "Control structures are blocks of code that determine the flow of program execution.\n\nTypes of control structures:\n- Conditional statements (if-else)\n- Loops (for, while)\n- Switch statements"
            ]
        ]
    ],
    'Web Development' => [
        'title' => 'Web Development Fundamentals',
        'sections' => [
            [
                'title' => '1. Introduction to Web Development',
                'content' => "Web development is the work involved in developing a website for the Internet or an intranet.\n\nKey topics:\n- HTML Basics\n- CSS Styling\n- JavaScript Fundamentals\n- Web Design Principles"
            ],
            [
                'title' => '2. HTML and CSS',
                'content' => "HTML (HyperText Markup Language) is the standard markup language for creating web pages.\n\nCSS (Cascading Style Sheets) is used to style and layout web pages.\n\nTopics covered:\n- HTML Structure\n- CSS Selectors\n- Layout Techniques\n- Responsive Design"
            ],
            [
                'title' => '3. JavaScript Basics',
                'content' => "JavaScript is a programming language that enables interactive web pages.\n\nKey concepts:\n- Variables and Data Types\n- Functions\n- DOM Manipulation\n- Event Handling"
            ]
        ]
    ],
    'Algebra' => [
        'title' => 'Introduction to Algebra',
        'sections' => [
            [
                'title' => '1. Basic Algebraic Concepts',
                'content' => "Algebra is a branch of mathematics that deals with symbols and the rules for manipulating these symbols.\n\nKey concepts covered in this section:\n- Variables and Expressions\n- Equations and Inequalities\n- Functions and Graphs\n- Polynomials"
            ],
            [
                'title' => '2. Linear Equations',
                'content' => "Linear equations are equations of the first degree, meaning they contain variables raised only to the first power.\n\nTopics covered:\n- Slope-Intercept Form\n- Point-Slope Form\n- Standard Form\n- Graphing Linear Equations"
            ],
            [
                'title' => '3. Quadratic Equations',
                'content' => "Quadratic equations are equations of the second degree, containing variables raised to the second power.\n\nKey concepts:\n- Factoring\n- Completing the Square\n- Quadratic Formula\n- Graphing Quadratic Functions"
            ]
        ]
    ],
    'Marketing' => [
        'title' => 'Marketing Fundamentals',
        'sections' => [
            [
                'title' => '1. Introduction to Marketing',
                'content' => "Marketing is the process of promoting and selling products or services.\n\nKey concepts covered in this section:\n- Marketing Mix (4 P's)\n- Market Research\n- Consumer Behavior\n- Marketing Strategy"
            ],
            [
                'title' => '2. Digital Marketing',
                'content' => "Digital marketing encompasses all marketing efforts that use electronic devices or the internet.\n\nTopics covered:\n- Social Media Marketing\n- Content Marketing\n- Email Marketing\n- Search Engine Optimization (SEO)"
            ],
            [
                'title' => '3. Brand Management',
                'content' => "Brand management is the process of creating and maintaining a brand's identity and value.\n\nKey concepts:\n- Brand Positioning\n- Brand Identity\n- Brand Communication\n- Brand Equity"
            ]
        ]
    ],
    'UI/UX Design' => [
        'title' => 'UI/UX Design Principles',
        'sections' => [
            [
                'title' => '1. Introduction to UI/UX Design',
                'content' => "UI/UX design focuses on creating user-friendly and visually appealing digital products.\n\nKey concepts covered in this section:\n- User Interface (UI) Design\n- User Experience (UX) Design\n- Design Principles\n- User Research"
            ],
            [
                'title' => '2. Design Process',
                'content' => "The design process involves several stages from research to implementation.\n\nTopics covered:\n- User Research\n- Wireframing\n- Prototyping\n- User Testing"
            ],
            [
                'title' => '3. Design Tools and Techniques',
                'content' => "Modern design tools and techniques help create effective user interfaces.\n\nKey concepts:\n- Design Systems\n- Color Theory\n- Typography\n- Responsive Design"
            ]
        ]
    ]
];

// Function to generate PDF for a course
function generateCoursePDF($courseName, $materials) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('E-Learning System');
    $pdf->SetTitle($materials['title']);
    
    // Set default header data
    $pdf->SetHeaderData('', 0, $materials['title'], 'Course Materials');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Add content
    foreach ($materials['sections'] as $section) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $section['title'], 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, $section['content'], 0, 'L');
        $pdf->Ln(10);
    }
    
    // Save the PDF
    $pdf->Output('course_materials/pdfs/' . strtolower(str_replace(' ', '_', $courseName)) . '_materials.pdf', 'F');
}

// Generate PDFs for all courses
foreach ($courseMaterials as $courseName => $materials) {
    generateCoursePDF($courseName, $materials);
}

echo "Course materials have been generated successfully!";
?> 