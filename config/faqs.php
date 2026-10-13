<?php

/*
 | FAQ content for the landing page preview and the dedicated /faqs page.
 | The first three questions under "General" are your original FAQs.
 | The others are drafts: edit them so they match what DocuMate actually does.
 */

return [

    'General' => [
        [
            'q' => 'What is DocuMate?',
            'a' => 'A digital system designed to streamline student transactions, improve workflow efficiency, and ensure transparent approvals.',
        ],
        [
            'q' => 'Who can use the system?',
            'a' => 'Students, officers, and administrators within the institution can access and utilize the system based on their roles.',
        ],
        [
            'q' => 'Is my data secure?',
            'a' => 'Yes. The system implements role-based access control and secure authentication to protect user data and ensure privacy.',
        ],
        [
            'q' => 'Which office does DocuMate support?',
            'a' => 'DocuMate supports the Office of the Vice President for Student Development (VPSD) of Leyte Normal University.',
        ],
    ],

    'Accounts & Access' => [
        [
            'q' => 'How do I create an account?',
            'a' => 'Select "Sign up here" on the landing page, fill in the registration form, and submit it. Once your account is ready, sign in with your credentials.',
        ],
        [
            'q' => 'What can I see after signing in?',
            'a' => 'What you see depends on your role. Students see their own requests and records, while authorized VPSD staff see the tools needed to manage transactions.',
        ],
        [
            'q' => 'I forgot my password. What should I do?',
            'a' => 'Use the password reset option on the sign-in page. If you still cannot get in, contact the VPSD office or the system administrators.',
        ],
    ],

    'Documents & Transactions' => [
        [
            'q' => 'What can I do in DocuMate?',
            'a' => 'You can submit documents and requests, track the status of your transactions, manage appointments, and keep your records in one place.',
        ],
        [
            'q' => 'How do I know the status of my request?',
            'a' => 'Open your transactions after signing in. Each request shows its current status, so you do not need to visit the office to ask.',
        ],
        [
            'q' => 'Can VPSD staff find my past records?',
            'a' => 'Yes. Authorized staff can search and retrieve records from the centralized system instead of looking through scattered physical files.',
        ],
    ],

    'Security & Privacy' => [
        [
            'q' => 'Who can view my documents?',
            'a' => 'Only you and the authorized personnel who need them to process your transaction. Access is controlled by user roles.',
        ],
        [
            'q' => 'Where can I read the Privacy Policy and Terms?',
            'a' => 'Both are linked in the footer of every page. Select Privacy Policy or Terms & Conditions to open them.',
        ],
    ],

];