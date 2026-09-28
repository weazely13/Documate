{{--
    resources/views/partials/terms-content.blade.php

    Same legal text as the landing page's #modal-terms body.
    Included by: login.blade.php, register.blade.php, and the landing page
    (swap the landing page's inline legal-content block for this same
    @include if you want a single source of truth).
--}}

<style>
    .legal-content h4 { font-weight: 700; color: #1f2937; margin-top: 1.25rem; margin-bottom: .4rem; }
    .legal-content h4:first-child { margin-top: 0; }
    .legal-content p { margin-bottom: .6rem; }
    .legal-content ul { list-style: disc; padding-left: 1.25rem; margin-bottom: .6rem; }
    .legal-content li { margin-bottom: .25rem; }
</style>

<div class="legal-content">

    <p>Welcome to VPSD DocuMate, a centralized student document filing and transaction workflow system developed for the Office of the Vice President for Student Development (VPSD) of Leyte Normal University (LNU).</p>
    <p>By creating an account, accessing, or using VPSD DocuMate, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.</p>

    <h4>1. Purpose of the System</h4>
    <p>VPSD DocuMate is designed to support the digital management of student-related documents and transactions handled by the Office of the Vice President for Student Development. The system may provide functions including:</p>
    <ul>
        <li>Student registration and account management;</li>
        <li>Submission and management of document-related requests;</li>
        <li>Appointment scheduling for VPSD transactions;</li>
        <li>Digital access to transaction forms;</li>
        <li>Uploading of completed transaction documents;</li>
        <li>Document filing and record retrieval;</li>
        <li>Clearance-related status monitoring;</li>
        <li>Verification of authorized users and signatories;</li>
        <li>Document and transaction status tracking;</li>
        <li>Optical character recognition (OCR) and/or artificial intelligence-assisted document processing;</li>
        <li>Searching and retrieving authorized student transaction records; and</li>
        <li>Other functions necessary for the administration of VPSD student services.</li>
    </ul>

    <h4>2. Eligibility and Account Registration</h4>
    <p>Use of VPSD DocuMate is limited to authorized users of Leyte Normal University and individuals who are permitted to transact with the Office of the Vice President for Student Development.</p>
    <p>Users are responsible for providing accurate and truthful information during registration and throughout their use of the system. Users must not:</p>
    <ul>
        <li>Register using another person's identity;</li>
        <li>Provide false, misleading, or fraudulent information;</li>
        <li>Create unauthorized accounts;</li>
        <li>Share their account credentials with another person;</li>
        <li>Attempt to access another user's account; or</li>
        <li>Allow unauthorized persons to use their account.</li>
    </ul>
    <p>The University may require additional verification before granting access to certain records or functions.</p>

    <h4>3. Account Security</h4>
    <p>Users are responsible for maintaining the confidentiality of their account credentials. Users must immediately report suspected unauthorized access, compromised credentials, or suspicious activity to the appropriate VPSD personnel or system administrator.</p>
    <p>The system administrator may temporarily restrict or suspend an account when necessary to protect student records, system security, or University resources.</p>

    <h4>4. Accuracy of Information</h4>
    <p>Users are responsible for ensuring that the information they submit is accurate, complete, and current. Submitting incorrect, incomplete, altered, or misleading information may result in delays, additional verification, rejection of a transaction, or other appropriate administrative action.</p>
    <p>VPSD DocuMate is intended to assist with the processing and management of transactions. It does not replace official University procedures, approvals, or requirements unless expressly authorized by the University.</p>

    <h4>5. Document Uploads and Submissions</h4>
    <p>Users may be required to upload photographs, scans, or digital copies of completed forms and other transaction-related documents. Uploaded documents must:</p>
    <ul>
        <li>Belong to or legitimately concern the transaction being submitted;</li>
        <li>Be readable and sufficiently clear for verification;</li>
        <li>Contain truthful and accurate information; and</li>
        <li>Comply with applicable University requirements.</li>
    </ul>
    <p>Users must not upload malicious files, intentionally falsified documents, inappropriate materials, or documents belonging to another person without proper authorization.</p>
    <p>The University may review submitted documents for verification, processing, record keeping, and administrative purposes.</p>

    <h4>6. Digital Signatures and Signatory Verification</h4>
    <p>Certain transactions may require signatures or approval from authorized individuals or offices. Where applicable, VPSD DocuMate may provide mechanisms for identifying or verifying authorized signatories.</p>
    <p>A user's upload of a document containing a signature does not, by itself, constitute official University approval. Official recognition of a signature, approval, or authorization remains subject to applicable University procedures and verification requirements.</p>
    <p>Users must not forge, reproduce, alter, or falsely represent another person's signature or authorization.</p>

    <h4>7. Clearance and Organizational Verification</h4>
    <p>Some student transactions may involve clearance information or verification by authorized student organizations or University offices. Only authorized users may update or verify clearance-related information.</p>
    <p>Users must not falsely mark a student as cleared, approved, verified, or completed.</p>
    <p>Clearance information displayed in the system is subject to the authority and verification procedures of the responsible University office or authorized organization.</p>

    <h4>8. AI-Assisted and Automated Processing</h4>
    <p>VPSD DocuMate may use artificial intelligence and automated technologies to assist with certain functions, such as document analysis, text extraction, classification, information organization, or identifying relevant University offices.</p>
    <p>AI-generated or automatically extracted information is intended to assist users and authorized personnel. It may contain errors and should not automatically be treated as an official determination.</p>
    <p>Authorized personnel may review, correct, confirm, or reject information generated or extracted by automated systems. Official decisions regarding student transactions, document approval, clearance, and other University matters remain subject to authorized human review and applicable University policies.</p>

    <h4>9. Acceptable Use</h4>
    <p>Users agree to use VPSD DocuMate only for legitimate University-related purposes. Users must not:</p>
    <ul>
        <li>Attempt to gain unauthorized access to the system;</li>
        <li>Access records without authorization;</li>
        <li>Modify or delete another person's records;</li>
        <li>Circumvent system security measures;</li>
        <li>Interfere with system operation;</li>
        <li>Introduce malicious software or harmful code;</li>
        <li>Use the system to impersonate another person;</li>
        <li>Submit fraudulent documents or information;</li>
        <li>Harvest, copy, or distribute student records without authorization; or</li>
        <li>Use information obtained through the system for unauthorized purposes.</li>
    </ul>
    <p>Violations may result in account restrictions, administrative action, and/or other remedies available under applicable University policies and Philippine law.</p>

    <h4>10. Student Records and Confidential Information</h4>
    <p>VPSD DocuMate may contain confidential student and transaction-related information. Users may only access information necessary for their authorized responsibilities.</p>
    <p>Users must not disclose, reproduce, download, screenshot, distribute, or otherwise use confidential student information outside the scope of their authorized activities.</p>

    <h4>11. System Availability</h4>
    <p>The University will make reasonable efforts to maintain the availability and functionality of VPSD DocuMate. However, temporary interruptions may occur because of:</p>
    <ul>
        <li>System maintenance;</li>
        <li>Internet connectivity problems;</li>
        <li>Server or hosting issues;</li>
        <li>Security incidents;</li>
        <li>Software or hardware failures;</li>
        <li>Third-party service interruptions; or</li>
        <li>Other circumstances beyond the reasonable control of the system administrators.</li>
    </ul>
    <p>The University does not guarantee uninterrupted or error-free operation of the system.</p>

    <h4>12. Modification of the System</h4>
    <p>The University and authorized system administrators may modify, update, add, or remove system features when necessary for operational, security, technical, or administrative reasons.</p>
    <p>Changes to important policies or requirements may be communicated through the system or other appropriate University channels.</p>

    <h4>13. Suspension or Termination of Access</h4>
    <p>Access to VPSD DocuMate may be suspended, restricted, or terminated when:</p>
    <ul>
        <li>A user violates these Terms and Conditions;</li>
        <li>Unauthorized access or suspicious activity is detected;</li>
        <li>A user's University authorization expires;</li>
        <li>The account is no longer required;</li>
        <li>The system is undergoing security-related restrictions; or</li>
        <li>Suspension is otherwise necessary to protect University systems or records.</li>
    </ul>

    <h4>14. Intellectual Property</h4>
    <p>The software, system interface, documentation, branding, and other original materials associated with VPSD DocuMate are protected by applicable intellectual property laws and University policies.</p>
    <p>Users may not copy, modify, distribute, reverse engineer, or reproduce system components without appropriate authorization.</p>
    <p>Student-submitted documents and personal information remain subject to applicable ownership, privacy, and University policies.</p>

    <h4>15. Privacy</h4>
    <p>The collection and processing of personal information through VPSD DocuMate are governed by the system's Privacy Policy and applicable Philippine data protection laws and regulations.</p>
    <p>By using the system, users acknowledge that their personal information may be collected and processed for legitimate University-related purposes as described in the Privacy Policy.</p>

    <h4>16. Compliance with University Policies and Philippine Law</h4>
    <p>Use of VPSD DocuMate is subject to applicable Leyte Normal University policies, procedures, regulations, and applicable laws of the Republic of the Philippines.</p>
    <p>Where these Terms and Conditions conflict with an official University policy or applicable law, the applicable University policy or law shall prevail to the extent required.</p>

    <h4>17. Changes to These Terms</h4>
    <p>These Terms and Conditions may be updated when necessary to reflect changes in the system, University procedures, security requirements, or applicable laws. Users are encouraged to review the current version whenever they use VPSD DocuMate.</p>

    <h4>18. Contact and Concerns</h4>
    <p>For questions, concerns, account issues, or requests related to VPSD DocuMate, users may contact:</p>
    <p>
        Office: Office of the Vice President for Student Development<br>
        Institution: Leyte Normal University<br>
        Email: [Insert Official VPSD Email]<br>
        System Administrator: [Insert Name/Office, if applicable]
    </p>

    <h4>19. Acceptance</h4>
    <p>By selecting "I Agree," creating an account, or using VPSD DocuMate, you confirm that you have read and understood these Terms and Conditions and agree to comply with them.</p>
    <p>If you do not agree with these Terms and Conditions, you should not use the system.</p>

</div>