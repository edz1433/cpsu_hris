<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Data Privacy Notice</title>
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background: #f9f9f9;
      color: #333;
    }

    .header img, .footer img {
      width: 75%; 
      text-align: left; 
      margin-top: -15px; 
      margin-left: -16px;
      display: block;
    }

    .footer img {
        width: 100% !important;
    }

    .privacy-container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      padding: 30px 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      box-sizing: border-box;
    }

    h1 {
      text-align: center;
      font-size: 28px;
      color: #2c3e50;
      margin-bottom: 25px;
    }

    p {
      line-height: 1.8;
      font-size: 16px;
      margin-bottom: 15px;
    }

    ul {
      margin: 10px 0 20px 20px;
      padding-left: 15px;
    }

    ul li {
      margin-bottom: 8px;
    }

    strong {
      color: #2c3e50;
    }

    .btn-container {
      text-align: center;
      margin-top: 30px;
    }

    .btn-accept {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-size: 15px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn-accept:hover {
      background-color: #218838;
    }
  </style>
</head>
<body>

    <div class="header">
        <img src="{{ asset('Uploads/spms-header.jpg') }}" alt="Header Image">
    </div>
  <hr>
  <div class="privacy-container">
    <h1>Data Privacy Compliance</h1>

    <p><strong>Gentle Reminder:</strong> In compliance with <strong>Republic Act No. 10173</strong>, or the <strong>Data Privacy Act of 2012</strong>, the <strong>Central Philippines State University (CPSU)</strong> reaffirms its commitment to protect and respect your personal data.</p>

    <p>
      All data collected through the <strong>Human Resource Information System (HRIS)</strong> is managed with strict confidentiality, integrity, and security.
    </p>

    <p><strong>HRIS Modules include:</strong></p>
    <ul>
      <li>Leave Application</li>
      <li>Daily Time Record (DTR)</li>
      <li>Employee List Management</li>
      <li>Digital Signature Upload</li>
      <li>Leave Form Generation</li>
      <li>Strategic Performance Management System (SPMS)</li>
    </ul>

    <p><strong>Your personal data may be used to:</strong></p>
    <ul>
    <li>Facilitate leave application and approvals</li>
    <li>Monitor attendance and generate DTR reports</li>
    <li>Update and manage employee records</li>
    <li>Upload and store digital signature images for use in official HR documents</li>
    <li>Support employee performance evaluation by attaching uploaded signatures to official forms</li>
    <li>Generate official HR forms and support audits, compliance, and assessments</li>
    </ul>

    <p>
    The <strong>Management Information System (MIS) Office</strong> ensures that your data—such as name, employee ID, attendance records, performance ratings, and uploaded digital signature images—is securely processed in accordance with CPSU’s internal data protection standards and relevant legal requirements.
    </p>

    <p><strong>Your Consent Matters</strong></p>

    <p>
    By continuing to use the HRIS and submitting your information, you acknowledge and consent that your data may be collected and used to support HR functions such as attendance tracking, leave management, and performance evaluation.
    </p>

    <p>
    This includes the uploading and use of your digital signature image for generating and verifying official HR-related documents. CPSU remains committed to protecting your personal data—retaining it only for as long as necessary and safeguarding it from unauthorized access or misuse.
    </p>

  </div>

  <div class="footer">
    <img src="{{ asset('Uploads/dpa-footer.png') }}" alt="Footer Image">
  </div>

</body>
</html>
