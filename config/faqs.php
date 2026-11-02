<?php

/*
 | FAQ content for the landing page preview and the dedicated /faqs page.
 | The first three questions under "General" are your original FAQs.
 | Existing draft FAQs should be checked against current behavior. Form Processing
 | FAQs are based on the implemented document transaction workflow.
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
            'keywords' => ['who can use it', 'who uses DocuMate', 'eligible users'],
        ],
        [
            'q' => 'Is my data secure?',
            'a' => 'Yes. The system implements role-based access control and secure authentication to protect user data and ensure privacy.',
        ],
        [
            'q' => 'Which office does DocuMate support?',
            'a' => 'DocuMate is dedicated to the Office of the Vice President for Student Development (VPSD) at Leyte Normal University. Other LNU offices may provide supporting services or information, but VPSD is the office DocuMate is designed to serve.',
        ],
    ],

    'Accounts & Access' => [
        [
            'q' => 'How do I create a student account?',
            'a' => 'Select Create an account on the [sign-in page](/login). Complete the Personal and Academic sections, upload a clear JPG or PNG enrollment slip (up to 5 MB) for verification, then set and confirm your password and accept the Terms and Conditions and Privacy Policy. The form requires a student number, name, sex, birth date, email, contact number, college, program, year level, and academic status; organization and profile photo are optional. Applicants must be at least 15 years old. After the slip is verified and you register, DocuMate signs you in and opens the student dashboard.',
            'keywords' => ['create account', 'create an account', 'student account', 'register', 'registration', 'sign up', 'signup', 'new account', 'make an account', 'enroll in documate', 'join documate', 'account application', 'registration steps', 'requirements to register', 'what do i need to register', 'student registration', 'new student user'],
        ],
        [
            'q' => 'What email address and contact number can I use to register?',
            'a' => 'Registration accepts Gmail, Yahoo, or Outlook email addresses. Enter a Philippine mobile number in either 09XXXXXXXXX or +639XXXXXXXXX format. The email address and student number must not already be registered.',
            'keywords' => ['supported email', 'allowed email', 'email domain', 'gmail', 'yahoo', 'outlook', 'school email', 'university email', 'email not accepted', 'invalid email', 'phone number format', 'contact number format', 'philippine number', 'mobile number', 'duplicate email', 'student number already exists'],
        ],
        [
            'q' => 'How do I verify my student account with an enrollment slip?',
            'a' => 'During registration, upload a readable JPG or PNG enrollment slip no larger than 5 MB. For an existing inactive account, sign in and open the [verification page](/verify), then upload the current enrollment slip and select Verify Account. The verification checks the student and enrollment details extracted from the slip.',
            'keywords' => ['verify account', 'verify my account', 'account verification', 'enrollment verification', 'student verification', 'e-slip', 'eslip', 'enrollment slip', 'registration slip', 'upload enrollment', 'verify enrollment', 'account not verified', 'not verified', 'verification page', 'verification upload', 'verify account process'],
        ],
        [
            'q' => 'What should be visible on my enrollment slip for verification?',
            'a' => 'Use a clear, uncropped JPG or PNG image (up to 5 MB). The slip needs to show matching student number and name, the current semester and academic year, year level, an official enrollment stamp, and processing details: who processed it, their signature, and the processing date and time. Blurry or incomplete images may not pass the system’s checks.',
            'keywords' => ['e-slip requirements', 'enrollment slip requirements', 'what should be on my e-slip', 'what is checked', 'verification requirements', 'registrar stamp', 'enrollment stamp', 'officially enrolled', 'processed by', 'processor signature', 'date and time processed', 'why rejected', 'slip not accepted', 'slip not verified', 'ocr mismatch', 'document image'],
        ],
        [
            'q' => 'What should I do if my account verification fails?',
            'a' => 'Review the verification details for fields that did not match. Upload a clear, complete enrollment slip showing the required enrollment stamp and processing details, and check that its student and semester information matches your DocuMate profile. If the information in your profile is incorrect or the verification continues to fail, contact the VPSD office or a DocuMate administrator for assistance.',
            'keywords' => ['verification failed', 'failed verification', 'e-slip rejected', 'rejected verification', 'account verification error', 'ocr failed', 'no enrollment stamp', 'mismatch', 'incorrect student details', 'resubmit e-slip', 'try again verification', 'cannot verify account', 'verification unsuccessful'],
        ],
        [
            'q' => 'Why is my account inactive or why am I being sent to the verification page?',
            'a' => 'Inactive student and officer accounts are directed to the verification page. An account may be disabled when the verification deadline passes without a verified enrollment slip for that period, or an administrator may change its status. Upload the current slip on the verification page; a successful verification reactivates the account. If it remains inactive after verification, contact a DocuMate administrator.',
            'keywords' => ['inactive account', 'account inactive', 'disabled account', 'account disabled', 'suspended account', 'locked account', 'cannot access dashboard', 'redirected to verify', 'sent to verification', 'verification deadline', 'missed verification period', 'reactivate account', 'restore access', 'account deactivated'],
        ],
        [
            'q' => 'How do I sign in to my student account?',
            'a' => 'Open the sign-in page and enter either your student number or registered email address together with your password. An inactive account will be directed to enrollment verification before normal student pages are available.',
            'keywords' => ['login', 'log in', 'sign in', 'signin', 'student number login', 'login with email', 'cannot log in', 'cannot login', 'invalid credentials', 'password login', 'access student account'],
        ],
        [
            'q' => 'What are the password requirements when I register?',
            'a' => 'The registration form requires at least 8 characters, one number, one uppercase letter, and one symbol. Enter the same password in the confirmation field and accept both the Terms and Conditions and Privacy Policy before registering.',
            'keywords' => ['password requirements', 'password rules', 'strong password', 'password strength', 'minimum password', 'password must contain', 'uppercase password', 'password symbol', 'password number', 'password confirmation', 'password does not match', 'register password'],
        ],
        [
            'q' => 'What can I see after signing in?',
            'a' => 'What you see depends on your role. Students see their own requests and records, while authorized VPSD staff see the tools needed to manage transactions.',
            'keywords' => ['after login', 'after sign in', 'student dashboard', 'what can students access', 'student account access', 'account permissions'],
        ],
        [
            'q' => 'I forgot my password. What should I do?',
            'a' => 'Open the [password reset page](/forgot-password), enter the email address registered to your account, and submit the request. DocuMate will email a password-reset link if the request succeeds. If you cannot access that email or the reset does not work, contact a DocuMate administrator.',
            'keywords' => ['forgot password', 'forgot my password', 'reset password', 'password reset', 'change forgotten password', 'password recovery', 'cannot reset password', 'reset link', 'password email', 'lost password', 'account recovery'],
        ],
        [
            'q' => 'How do I update my student profile or change my password?',
            'a' => 'Open [Profile](/profile) to edit your personal and academic information and profile photo. Your student number is not editable. Officers cannot change their college, program, or organization. To change your password, open the password section in Profile, enter your current password, then enter and confirm the new password.',
            'keywords' => ['update profile', 'edit profile', 'change profile information', 'change email', 'change contact number', 'update student details', 'change program', 'change college', 'profile picture', 'profile photo', 'change password', 'update password', 'password in profile', 'student number change'],
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

    'Form Processing' => [
        [
            'q' => 'How do I complete and submit an online workflow form?',
            'a' => 'Open New Transaction and choose a form marked Online. Complete its required fields, save a draft if you are not ready to submit, then select Submit to send it to VPSD. Your name, student number, and program details come from your profile; some forms also require a saved e-signature or a photo. Use the form-specific instructions for exact requirements.',
            'keywords' => ['online form', 'online workflow form', 'workflow request', 'form', 'forms', 'document form', 'request form', 'document request', 'request a document', 'apply for a document', 'application', 'paperwork', 'submit', 'submission', 'send a request', 'file a request', 'fill out', 'fill in', 'complete', 'accomplish', 'enter information', 'start a request', 'start a transaction', 'new transaction', 'choose a document', 'save draft', 'submit to vpsd', 'how to process a form'],
        ],
        [
            'q' => 'What happens after I submit a document form?',
            'a' => 'The VPSD office reviews an online workflow form. It may be returned to you for corrections, rejected, or accepted for signatory approval. If corrections are requested, follow the note on the transaction, edit the form, and resubmit it. After all required approvals, VPSD completes the transaction and the official PDF becomes available on its transaction page. An appointment is needed only when the office or form instructions require one.',
            'keywords' => ['after submission', 'after submitting', 'after saving my form', 'what happens next', 'next step', 'next steps', 'workflow', 'process flow', 'processing steps', 'document processing', 'form processing', 'review', 'approval', 'appointment', 'schedule', 'sign', 'signature', 'signatory', 'collect signatures', 'print', 'pdf', 'download', 'bring the document', 'VPSD', 'attendance', 'verification', 'upload', 'completed', 'after i submit', 'what happens after submit'],
        ],
        [
            'q' => 'How can I check the processing status of my form?',
            'a' => 'Sign in and open Documents. Online workflow requests appear under Active while they are being processed and under History when finished, rejected, or voided. Open a request to see its current status, any action required from you, pending signatory, and activity timeline.',
            'keywords' => ['status', 'track', 'tracking', 'track my form', 'track my request', 'follow up', 'check progress', 'processing status', 'request status', 'document status', 'form status', 'stage', 'pending', 'in progress', 'completed', 'activity timeline', 'where is my form', 'where is my document', 'what is happening to my request', 'has my form been processed'],
        ],
        [
            'q' => 'What do the online transaction statuses mean?',
            'a' => 'Draft is saved but not submitted. Submitted is waiting for VPSD review. Under Review means VPSD is reviewing the form, or it is in the signatory stage when approvals are listed. Action Required means VPSD asked you to make changes and resubmit. Approved means all required signatories have approved and VPSD must complete final verification. Completed means the official PDF is ready to download. Rejected is closed; Voided means a completed record was withdrawn but retained in the history.',
            'keywords' => ['draft status', 'submitted status', 'under review status', 'action required status', 'approved status', 'completed status', 'rejected status', 'voided status', 'status meaning', 'what does approved mean', 'what does under review mean', 'what does action required mean', 'what does completed mean', 'transaction stages'],
        ],
        [
            'q' => 'Can I edit a document form after saving it?',
            'a' => 'An online workflow form can be edited while it is a draft. After submission, it is locked unless VPSD returns it with Action Required; then edit the requested information and resubmit it. A paper template workspace can be reopened and edited before you generate its PDF.',
            'keywords' => ['edit', 'change', 'update', 'modify', 'correct', 'correction', 'fix a mistake', 'wrong information', 'incorrect details', 'saved form', 'saved document', 're-save', 'resave', 'date field', 'can i revise', 'can i amend', 'action required', 'edit after submitting'],
        ],
        [
            'q' => 'How do I complete a paper template in New Transaction?',
            'a' => 'Choose a form marked Print in New Transaction, fill in the editable fields, and select Save to Workspace to keep your draft. Select Save PDF to generate the completed document. Follow any instructions printed on the form or provided by the responsible office for physical signatures, delivery, or appointments; these steps vary by form.',
            'keywords' => ['paper template', 'print form', 'form marked print', 'paper form', 'template', 'save to workspace', 'save pdf', 'generate pdf', 'print', 'physical form', 'hard copy', 'completed document', 'paper copy'],
        ],
        [
            'q' => 'What should I do if my form or uploaded document needs correction?',
            'a' => 'For an online workflow request marked Action Required, read the VPSD note, edit the form, and select Resubmit. If the request is Rejected, it is closed; contact VPSD if you need to know whether to start a new request. For a paper template, correct the saved workspace before generating a new PDF.',
            'keywords' => ['correction needed', 'needs correction', 'correct my form', 'correct my document', 'rejected upload', 'upload rejected', 'photo rejected', 'reupload', 're-upload', 'replace upload', 'missing information', 'wrong entry', 'error in form', 'form error', 'document error', 'action required', 'rejected'],
        ],
        [
            'q' => 'How do I download, print, or get signatures for my document?',
            'a' => 'For an online workflow form, wait until its status is Completed, then download the official PDF from the transaction page. Print it only if a physical copy is required. For a paper template, use Save PDF from its New Transaction workspace; follow the form or office instructions for any physical signatures.',
            'keywords' => ['download form', 'download document', 'print form', 'print document', 'save pdf', 'export pdf', 'get signatures', 'who signs', 'signature process', 'signatories', 'signed document', 'before appointment', 'official pdf'],
        ],
        [
            'q' => 'How do I book an appointment in DocuMate?',
            'a' => 'Book from Appointments or from an eligible document page using Set Appointment. Choose a purpose, select a document if the form is related to one, choose an available date and session, then confirm. The request starts as Pending and must be reviewed by the office. An approved appointment shows its queue number; a missed appointment can be reopened to request a new date.',
            'keywords' => ['book an appointment', 'booking an appointment', 'schedule an appointment', 'request an appointment', 'set appointment', 'appointment process', 'appointment steps', 'choose a date', 'appointment queue', 'reapply appointment'],
        ],
        [
            'q' => 'What happens to my document after my appointment?',
            'a' => 'Appointment handling depends on the appointment purpose. For an online workflow form, the transaction remains tracked separately in Documents; its completion depends on VPSD review and the required approvals. Do not assume that every form requires an appointment or a photo upload.',
            'keywords' => ['after appointment', 'after my appointment', 'final document', 'upload final copy', 'upload photo', 'scan document', 'digital copy', 'ai review', 'photo verification', 'mark attended', 'attendance recorded', 'when is it completed', 'complete my request'],
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

    'Campus Services' => [
        [
            'q' => 'Who handles student ID renewal, replacement, or reprinting?',
            'a' => 'Student ID printing is handled by the Business and Income Generation Office (BIGO), under the Auxiliary Services Office. LNU lists Mr. Jovert C. Espino under ID Printing Services. See the [LNU Auxiliary Services Office page](https://www.lnu.edu.ph/auxiliary-services-office/).' . "\n\nTo replace a lost or damaged student ID:\n1. Obtain certification or authorization from OSS or VPSD.\n2. Request a payment slip from the designated office.\n3. Pay the reprinting fee at the University Cashier. The supplied guidance lists PHP 200; confirm the current amount with BIGO before paying.",
            'keywords' => ['student id renewal', 'renew student id', 'id replacement', 'replace student id', 'lost student id', 'damaged student id', 'id reprinting', 'reprint id', 'id printing', 'who handles id', 'who is in charge of id', 'who is in charge for id renewal', 'id concerns'],
        ],
        [
            'q' => 'How do I request an LNU alumni ID?',
            'a' => 'The alumni ID guidance shared with DocuMate says to pay the processing fee at the University Cashier, then complete the Alumni ID form with the requested receipt image, white-background photo, and e-signature. The ID may be processed as a PDF or through the IGP Focal Person. The current official [Auxiliary Services Office page](https://www.lnu.edu.ph/auxiliary-services-office/) lists LNU ID services but does not publish the alumni form or fee, so confirm the current steps with Alumni Affairs or BIGO before paying.',
            'keywords' => ['alumni id', 'alumni identification card', 'request alumni id', 'alumni id form', 'igp focal person'],
        ],
    ],

];
