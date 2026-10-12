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
        [
            'q' => 'Do I need to visit the office to submit a request?',
            'a' => 'Many requests can be submitted online through DocuMate. However, some transactions may still require an office visit or additional verification.',
        ],
        [
            'q' => 'Who should I contact if DocuMate is not working??',
            'a' => 'Contact the designated DocuMate administrator or support personnel and provide a description of the issue you encountered.',
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
        [
            'q' => 'Can I change my account information?',
            'a' => 'You may update the account information permitted by the system. For details that require administrative changes, contact the system administrator.',
        ],
        [
            'q' => 'Can I use another student(s) account?',
            'a' => 'No. Each student should use their own account to protect personal information and ensure that transactions are recorded under the correct user.',
        ],
        [
            'q' => 'Can I log in to DocuMate on multiple devices?',
            'a' => 'You may be able to access your account from different devices, depending on the system(s) security settings. Always log out when using a shared or public device.',
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
        [
            'q' => 'What file formats can I upload?',
            'a' => 'Accepted file formats depend on the upload field. Follow the file type and size requirements displayed by DocuMate before submitting your document.',
        ],
        [
            'q' => 'How long does it take to process a document request?',
            'a' => 'Processing time varies depending on the document type, verification requirements, and workload of the responsible office. Monitor your request status for updates.',
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
         [
            'q' => 'Does DocuMate protect my personal information?',
            'a' => 'DocuMate is designed to protect student information through appropriate access controls and security measures. Users should also keep their login credentials confidential.',
        ],
        [
            'q' => 'What should I do if I notice unauthorized access to my account?',
            'a' => 'Report the incident to the DocuMate administrator immediately and change your password if you can still access your account.',
        ],
        [
            'q' => 'How does DocuMate handle my uploaded documents?',
            'a' => 'Uploaded documents are used to support the relevant school transaction and are handled according to the system(s) access permissions and applicable privacy policies.',
        ],
    ],

];