# 💳 E-Wallet System (Simplified Digital Wallet)

[![Deployed on](https://img.shields.io/badge/Deployed-Live-success)](http://meomeo.baby/)

A comprehensive web-based e-wallet application designed to mimic the core functionalities of modern digital wallets (like MoMo) and online banking platforms. This system securely handles user authentication, financial transactions, and administrative oversight with a strong emphasis on data consistency and security.

## 🚀 Live Demo
The application is deployed and publicly accessible at: **[meomeo.baby](http://meomeo.baby/)**
## 📚 Table of Contents

- [Live Demo](#-live-demo)
- [Security Features](#️-security-features)
- [Key Features](#-key-features)
  - [User Functionalities](#-user-functionalities-customer-role)
  - [Administrator Functionalities](#-administrator-functionalities-admin-role)
- [Tech Stack](#️-tech-stack)
- [How to Run Locally](#️-how-to-run-locally)
- [Testing Credentials](#-testing-credentials)
- [Implementation Team](#-implementation-team)
- [Acknowledgments](#-acknowledgments)
- [Project Structure](#-project-structure)
---

## 🛡️ Security Features
Security was the top priority in this application's architecture. The following protections are strictly enforced:

* **XSS (Cross-Site Scripting) Prevention:** All data is aggressively escaped before rendering HTML. Data from AJAX requests is never directly injected into the DOM without strict filtering.
* **CSRF (Cross-Site Request Forgery) Protection:** Every AJAX request requires a unique, validated CSRF token before the server processes the request.
* **SQL Injection Prevention:** 100% reliance on Prepared Statements for all database interactions.
* **Timing Attack Mitigation:** Implemented secure comparison methods during authentication processes.
* **UX Guardrails:** Submission buttons are temporarily disabled upon click to prevent duplicate transactions and ensure smooth page redirects.

---

## 🌟 Key Features

### 👤 User Functionalities (Customer Role)
* **Secure Authentication Workflow:** * Registration generates an automatic 6-character temporary password sent via email.
    * **First Login:** Users are strictly forced to change their password before accessing any features.
    * **Account Status:** New accounts enter a 'Pending Verification' state until an Admin reviews the uploaded ID card photos.
    * **Multi-Login:** Users can log in using either their registered Phone Number or Email.
* **Auto-Lock Security:** * 3 consecutive failed logins trigger a 1-minute temporary lock. 
    * Further failures after the temporary lock result in an indefinite lock requiring Admin intervention.
* **Password Recovery:** Automated OTP sent via email/SMS with a 1-minute validity window for secure resets.
* **Deposit Money:** Free top-ups via simulated credit cards.
    * *Card 111111:* Unlimited recharge.
    * *Card 222222:* Limit of 1,000,000 VND per transaction.
    * *Card 333333:* Always displays "Card is out of money."
* **Withdrawal:** Withdraw to card `111111`. Limited to 2 times/day, multiples of 50,000 VND, with a 5% fee. Transactions > 5,000,000 VND require Admin approval.
* **Money Transfer:** Internal P2P transfers. Users can choose who bears the 5% fee. Requires 6-digit OTP confirmation. Transactions > 5,000,000 VND require Admin approval.
* **Phone Cards:** Purchase scratch cards for Viettel, Mobifone, or Vinaphone with 0 VND transaction fees.
* **Transaction History:** A unified dashboard detailing all activities, including pending, approved, or cancelled statuses.

### 👑 Administrator Functionalities (Admin Role)
* **KYC Management:** Full oversight to Verify, Cancel, or request additional ID info for pending accounts.
* **System Moderation:** Functionality to unlock accounts disabled due to login failures.
* **Transaction Approval:** Dedicated queue to review and approve/reject high-value withdrawals or transfers (> 5,000,000 VND).

---

## 🛠️ Tech Stack
* **Backend:** PHP
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, JavaScript (Vanilla / AJAX)
* **Libraries:** Bootstrap, jQuery (UI & Styling)

---

## ⚙️ How to Run Locally

1.  **Environment Setup:** Ensure you have **XAMPP**, **WAMP**, or any PHP environment installed.
2.  **Database Initialization:** * Open phpMyAdmin.
    * Import the `database.sql` script located in the `/database` folder.
3.  **Deploy Locally:**
    * Place the project folder into your local server directory (`htdocs` or `www`).
    * Navigate to `http://localhost/[folder-name]/clients/pages/Login.php`.

---

## 🧪 Testing Credentials

**Admin Account:**
* **Phone:** `0000000000` / **Password:** `000000`

**User - Waiting for Info (Verified = 2):**
* **Phone:** `0987654321` / **Password:** `123456`

**User - Disabled (Verified = 4):**
* **Phone:** `0909090909` / **Password:** `123456`

## 👥 Implementation Team

This project was developed by students from **Ton Duc Thang University (TDTU)**.

| Name | Student ID | Role |
| :--- | :--- | :--- |
| **Tran Hoang Khai** | 523v0003 | **Leader**, Code Review, Developer, Database Design |
| **Lam Gia Vu** | 523v0003 | Developer, UI/UX Design |

---
## 🙏 Acknowledgments

* **Ton Duc Thang University:** For providing the academic framework and resources to complete this project.
* **Inspired by:** Real-world digital wallet platforms like MoMo and ZaloPay.
* **Built with ❤️:** Using PHP, MySQL, and the open-source community's tools.
* **Special Thanks:** To our course instructor for guidance on web security and system architecture.

⭐ *If you find this project useful for your studies, please consider giving it a star!* ⭐

---
## 📁 Project Structure
```text
E-Wallet_System/
│
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── Buycard.css
│   │   ├── changePassword.css
│   │   ├── deposit.css
│   │   ├── forgot.css
│   │   ├── home.css
│   │   ├── login.css
│   │   ├── profile.css
│   │   ├── register.css
│   │   ├── style.css
│   │   ├── transaction.css
│   │   ├── transfer.css
│   │   └── withdraw.css
│   │
│   ├── img/
│   │   └── meomeoBackground.jpg
│   │
│   └── js/
│       ├── admin.js
│       ├── Buycard.js
│       ├── deposit.js
│       ├── home.js
│       ├── main.js
│       ├── profile.js
│       ├── transfer.js
│       └── withdraw.js
│
├── modules/
│   ├── adminLogic.php
│   ├── db_connection.php
│   ├── formatMoney.php
│   ├── generateCode.php
│   ├── getTodayWithdrawCount.php
│   ├── handleFailedLogin.php
│   ├── isValidCard.php
│   ├── isValidDate.php
│   ├── logout.php
│   ├── lookup_user.php
│   ├── selectfromuserbyemail.php
│   ├── sendOTP.php
│   ├── transfer.php
│   ├── usertype.php
│   └── verifypass.php
│
├── pages/
│   ├── Admin_dashboard.php
│   ├── Buycard.php
│   ├── ChangePassword.php
│   ├── Deposit.php
│   ├── ForgotPassword.php
│   ├── Home.php
│   ├── Login.php
│   ├── Profile.php
│   ├── Register.php
│   ├── Transactions.php
│   ├── Transaction_detail.php
│   ├── Transfer.php
│   └── Withdraw.php
│
└── src/
     ├── footer.php
     ├── header.php
     └── headerOutSide.php
```
---
[⬆ Back to Top](#-e-wallet-system-simplified-digital-wallet)
