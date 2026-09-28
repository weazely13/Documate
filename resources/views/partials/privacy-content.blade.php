{{--
    resources/views/partials/privacy-content.blade.php

    Same legal text as the landing page's #modal-privacy body.
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

    <p>This Privacy Policy explains how VPSD DocuMate collects, uses, stores, protects, and manages personal information processed through the system.</p>
    <p>VPSD DocuMate is intended to support the centralized filing and transaction workflow of the Office of the Vice President for Student Development (VPSD) of Leyte Normal University (LNU).</p>
    <p>The system is designed with consideration of the Data Privacy Act of 2012 (Republic Act No. 10173) and its applicable implementing rules, regulations, and guidance.</p>

    <h4>1. Information We Collect</h4>
    <p>Depending on the transaction and user's role, VPSD DocuMate may collect and process information such as:</p>
    <p class="font-semibold text-gray-700">A. Account Information</p>
    <ul>
        <li>Full name;</li>
        <li>Student number or other University identifier;</li>
        <li>University email address;</li>
        <li>Contact information;</li>
        <li>Program, year level, or other academic identification information necessary for the transaction;</li>
        <li>Account credentials and authentication-related information; and</li>
        <li>User role or authorization information.</li>
    </ul>
    <p class="font-semibold text-gray-700">B. Transaction Information</p>
    <p>The system may collect information related to a student's transaction, including:</p>
    <ul>
        <li>Type of transaction;</li>
        <li>Transaction details;</li>
        <li>Appointment date and time;</li>
        <li>Transaction status;</li>
        <li>Submission dates;</li>
        <li>Office or organization involved;</li>
        <li>Clearance-related information;</li>
        <li>Verification information; and</li>
        <li>Other information necessary to process the requested service.</li>
    </ul>
    <p class="font-semibold text-gray-700">C. Uploaded Documents</p>
    <p>Users may upload photographs, scans, forms, and other documents required for a transaction. These documents may contain personal information and may be processed for document verification, record keeping, transaction processing, and related legitimate University purposes.</p>
    <p class="font-semibold text-gray-700">D. Automatically Generated or Extracted Information</p>
    <p>The system may use automated technologies, including OCR and/or artificial intelligence-assisted document processing, to extract or classify information from submitted documents. Examples may include:</p>
    <ul>
        <li>Names;</li>
        <li>Dates;</li>
        <li>Document types;</li>
        <li>Transaction identifiers;</li>
        <li>Signatory information;</li>
        <li>Other text appearing in submitted documents; and</li>
        <li>Other metadata necessary for document organization.</li>
    </ul>
    <p>Automatically extracted information may be reviewed and corrected by authorized personnel.</p>

    <h4>2. How We Use Personal Information</h4>
    <p>Personal information collected through VPSD DocuMate may be used for purposes such as:</p>
    <ul>
        <li>Creating and managing user accounts;</li>
        <li>Verifying user identity and authorization;</li>
        <li>Processing student transactions;</li>
        <li>Managing appointments;</li>
        <li>Filing and retrieving transaction records;</li>
        <li>Processing document submissions;</li>
        <li>Supporting clearance-related processes;</li>
        <li>Verifying authorized signatories;</li>
        <li>Monitoring transaction status;</li>
        <li>Responding to authorized requests;</li>
        <li>Maintaining accurate University records;</li>
        <li>Improving system security and reliability;</li>
        <li>Generating administrative reports and operational information; and</li>
        <li>Complying with applicable University policies and legal obligations.</li>
    </ul>
    <p>Personal information will not be processed for purposes incompatible with the purpose for which it was collected, except where otherwise permitted or required by applicable law.</p>

    <h4>3. Legal Basis for Processing</h4>
    <p>Personal information may be processed where permitted under applicable Philippine data protection laws, including circumstances involving:</p>
    <ul>
        <li>Performance of a lawful and legitimate University function;</li>
        <li>Compliance with a legal obligation;</li>
        <li>Fulfillment of a contractual or similar lawful obligation, where applicable;</li>
        <li>Protection of vital interests, where applicable;</li>
        <li>Consent, when consent is the appropriate legal basis; or</li>
        <li>Other lawful bases recognized under applicable data protection laws and regulations.</li>
    </ul>
    <p>The appropriate legal basis may depend on the specific transaction and processing activity.</p>

    <h4>4. AI-Assisted Processing</h4>
    <p>VPSD DocuMate may use artificial intelligence or automated document-processing technologies to assist with document analysis, extraction, classification, or organization.</p>
    <p>AI processing does not replace authorized University personnel in making official decisions concerning student transactions. Where automated processing produces inaccurate or incomplete information, authorized personnel may review and correct the resulting information.</p>
    <p>Users should avoid submitting information that is unrelated to the transaction.</p>

    <h4>5. Who May Access Personal Information</h4>
    <p>Access to personal information is restricted based on the user's role and authorization. Depending on the transaction, authorized access may be provided to:</p>
    <ul>
        <li>The student concerned;</li>
        <li>Authorized VPSD personnel;</li>
        <li>Authorized system administrators;</li>
        <li>Authorized University offices or personnel involved in the transaction;</li>
        <li>Authorized student organization representatives where applicable to clearance-related processes; and</li>
        <li>Other authorized persons or entities when required or permitted by law.</li>
    </ul>
    <p>Access should be limited to information necessary for the person's authorized function.</p>

    <h4>6. Data Sharing and Disclosure</h4>
    <p>LNU does not intend to sell personal information collected through VPSD DocuMate. Personal information may be disclosed or made accessible when reasonably necessary for legitimate University functions, including:</p>
    <ul>
        <li>Processing a student's requested transaction;</li>
        <li>Verification by an authorized University office;</li>
        <li>Clearance processing;</li>
        <li>System maintenance or technical support by authorized service providers;</li>
        <li>Compliance with legal or regulatory requirements;</li>
        <li>Responding to lawful orders or requests from competent authorities; or</li>
        <li>Other circumstances permitted by applicable law.</li>
    </ul>
    <p>Where third-party service providers process information on behalf of the University, appropriate contractual, organizational, and technical safeguards should be applied as required by applicable law and University policy.</p>

    <h4>7. Data Retention</h4>
    <p>Personal information and transaction records may be retained for as long as necessary to:</p>
    <ul>
        <li>Complete the relevant transaction;</li>
        <li>Maintain official University records;</li>
        <li>Support legitimate administrative purposes;</li>
        <li>Comply with applicable retention requirements;</li>
        <li>Resolve disputes or inquiries; or</li>
        <li>Fulfill other legal or institutional obligations.</li>
    </ul>
    <p>Retention periods may vary depending on the type of record and applicable University policies. When information is no longer required to be retained, it should be securely deleted, destroyed, anonymized, or otherwise disposed of in accordance with applicable policies and requirements.</p>

    <h4>8. Data Security</h4>
    <p>Reasonable organizational, physical, and technical measures should be implemented to protect personal information against unauthorized access, alteration, disclosure, loss, destruction, or other unlawful processing. Security measures may include:</p>
    <ul>
        <li>User authentication;</li>
        <li>Role-based access control;</li>
        <li>Password protection;</li>
        <li>Restricted access to sensitive records;</li>
        <li>Secure storage;</li>
        <li>Access monitoring;</li>
        <li>System backups;</li>
        <li>Security updates; and</li>
        <li>Other appropriate technical and organizational safeguards.</li>
    </ul>
    <p>No electronic system can guarantee absolute security. Users should therefore also protect their account credentials and immediately report suspected unauthorized access.</p>

    <h4>9. User Responsibilities</h4>
    <p>Users are responsible for:</p>
    <ul>
        <li>Providing accurate information;</li>
        <li>Keeping their account credentials confidential;</li>
        <li>Using the system only for authorized purposes;</li>
        <li>Protecting information accessed through the system;</li>
        <li>Avoiding unauthorized disclosure of student records; and</li>
        <li>Reporting suspected security or privacy incidents.</li>
    </ul>
    <p>Users must not access, copy, modify, distribute, or disclose personal information beyond what their role and authorization permit.</p>

    <h4>10. Data Privacy Rights</h4>
    <p>Subject to applicable laws, regulations, and legitimate University requirements, individuals may have rights concerning their personal information, including the right to:</p>
    <ul>
        <li>Be informed about the processing of their personal information;</li>
        <li>Access personal information held about them;</li>
        <li>Request correction of inaccurate or incomplete information;</li>
        <li>Object to certain forms of processing;</li>
        <li>Request deletion or blocking where legally applicable;</li>
        <li>Withdraw consent where processing is based on consent;</li>
        <li>Request data portability where applicable; and</li>
        <li>Lodge a complaint regarding the processing of their personal information.</li>
    </ul>
    <p>Some rights may be subject to limitations or exceptions provided by law. Requests may be submitted to the appropriate University office or Data Protection Officer.</p>

    <h4>11. Privacy and Security Incidents</h4>
    <p>If a user believes that personal information has been accessed, disclosed, altered, lost, or otherwise processed without authorization, the incident should be reported immediately to the appropriate VPSD personnel, system administrator, or University Data Protection Officer.</p>
    <p>The University may investigate reported incidents and take appropriate measures in accordance with applicable laws, regulations, and University policies.</p>

    <h4>12. Cookies and Technical Information</h4>
    <p>VPSD DocuMate may use cookies, sessions, logs, and similar technical mechanisms necessary for functions such as:</p>
    <ul>
        <li>User authentication;</li>
        <li>Maintaining active sessions;</li>
        <li>Security;</li>
        <li>System functionality;</li>
        <li>Performance monitoring; and</li>
        <li>Troubleshooting.</li>
    </ul>
    <p>Technical information collected through these mechanisms should only be used for legitimate system and administrative purposes.</p>

    <h4>13. Third-Party Services</h4>
    <p>Certain system functions may rely on external or third-party technologies, such as hosting services, authentication services, AI services, document-processing services, email services, or other technical infrastructure.</p>
    <p>Where third-party services process personal information, their use should be subject to appropriate privacy and security safeguards and applicable agreements or requirements. Users should be informed of significant third-party processing where required by applicable law.</p>

    <h4>14. Children's Privacy</h4>
    <p>VPSD DocuMate is primarily intended for authorized University users. Where personal information relating to minors is processed, additional safeguards and requirements applicable under Philippine law and University policy shall apply.</p>

    <h4>15. Changes to This Privacy Policy</h4>
    <p>This Privacy Policy may be updated to reflect changes in:</p>
    <ul>
        <li>System functionality;</li>
        <li>University procedures;</li>
        <li>Data processing activities;</li>
        <li>Security practices;</li>
        <li>Applicable laws or regulations; or</li>
        <li>Privacy requirements.</li>
    </ul>
    <p>The latest version should be made available through VPSD DocuMate or an appropriate University channel.</p>

    <h4>16. Contact Information</h4>
    <p>For privacy-related questions, requests, or concerns, contact:</p>
    <p>
        Office: Office of the Vice President for Student Development<br>
        Institution: Leyte Normal University<br>
        Email: [Insert Official VPSD Email]<br>
        Data Protection Officer: [Insert Official DPO Name/Office]<br>
        DPO Email: [Insert Official DPO Email]
    </p>

    <h4>17. Acknowledgment</h4>
    <p>By using VPSD DocuMate, users acknowledge that they have been provided information regarding the collection and processing of their personal information as described in this Privacy Policy.</p>
    <p>Where consent is required as the legal basis for a particular processing activity, the appropriate consent mechanism shall be provided separately. This Privacy Policy does not remove or limit any rights provided to individuals under applicable Philippine data protection laws.</p>

</div>