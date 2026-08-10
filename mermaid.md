# EduGrant ER Diagram (Mermaid)

```mermaid
erDiagram
    admin ||--o{ applications : approves
    admin ||--o{ receipts : uploads
    admin ||--o{ notifications : receives

    student ||--o{ applications : applies
    student ||--o{ bank_details : has
    student ||--o{ receipts : owns
    student ||--o{ notifications : receives
    student ||--o{ contact_messages : sends

    schemes ||--o{ applications : gets
    schemes ||--o{ reviewer_scheme : assigned
    reviewers ||--o{ reviewer_scheme : assigned
    reviewers ||--o{ application_reviews : gives
    reviewers ||--o{ notifications : receives

    applications ||--o| scholarship_recipients : becomes
    applications ||--o{ receipts : has
    applications ||--o{ application_reviews : gets

    scholarship_recipients ||--o{ payment_records : receives
    bank_details ||--o{ payment_records : pays_to

    admin {
        int id PK
        string name
        string email
        string password
    }

    student {
        int id PK
        string roll_no
        string name
        string email
        string phone
        string profile_image
        string password
        string gender
        string address
        timestamp created_at
    }

    schemes {
        int id PK
        string scheme_name
        string amount
        date deadline
        enum status
        text description
        text eligibility
        string image
        timestamp created_at
    }

    reviewers {
        int id PK
        string name
        string department
        string email
        string password
        timestamp created_at
    }

    applications {
        int id PK
        int student_id FK
        int scheme_id FK
        string application_no
        decimal family_income
        date apply_date
        enum status
        int approved_by FK
        timestamp approved_at
        enum payment_status
        string father_occupation
        string mother_occupation
        decimal grade_10_marks
        int num_siblings
        string house_photo
        string household_registration
        text reason
        timestamp created_at
    }

    scholarship_recipients {
        int id PK
        int application_id FK
        year start_year
        timestamp created_at
    }

    bank_details {
        int id PK
        int student_id FK
        string bank_name
        string account_number
        string account_holder
        boolean is_verified
        timestamp updated_at
        timestamp created_at
    }

    receipts {
        int id PK
        int student_id FK
        int application_id FK
        string filename
        int uploaded_by FK
        timestamp created_at
    }

    payment_records {
        int id PK
        int recipient_id FK
        int bank_id FK
        decimal amount
        string academic_year
        string semester
        date payment_date
        timestamp created_at
    }

    notifications {
        int id PK
        int student_id FK
        int admin_id FK
        int reviewer_id FK
        string title
        text message
        boolean is_read
        string type
        timestamp created_at
    }

    contact_messages {
        int id PK
        int student_id FK
        string full_name
        string email
        string subject
        text message
        boolean is_read
        timestamp created_at
    }

    application_reviews {
        int id PK
        int application_id FK
        int reviewer_id FK
        enum recommendation
        text remarks
        timestamp reviewed_at
    }

    reviewer_scheme {
        int id PK
        int scheme_id FK
        int reviewer_id FK
    }
```

# Use Case Diagrams

## Student

```mermaid
usecaseDiagram
    actor Student

    Student --> (Register Account)
    Student --> (Login)
    Student --> (Logout)
    Student --> (Browse Schemes)
    Student --> (Apply to Scheme)
    Student --> (Submit Bank Details)
    Student --> (Track Application Status)
    Student --> (View Application Details)
    Student --> (Download Receipt)
    Student --> (View Notifications)
    Student --> (Manage Profile)
    Student --> (Send Message to Admin)
    (Apply to Scheme) ..> (Login) : <<include>>
    (Submit Bank Details) ..> (Login) : <<include>>
```

## Admin

```mermaid
usecaseDiagram
    actor Admin

    Admin --> (Login)
    Admin --> (Logout)
    Admin --> (View Dashboard)
    Admin --> (Manage Schemes)
    Admin --> (Manage Reviewers & Assign Schemes)
    Admin --> (Review & Approve/Reject Applications)
    Admin --> (Verify Bank & Disburse Funds)
    Admin --> (Manage Recipients)
    Admin --> (Record Disbursements)
    Admin --> (View Reports)
    Admin --> (Read Student Messages)
    Admin --> (View Notifications)
    Admin --> (Manage Students)
    Admin --> (Search Records)
    (Verify Bank & Disburse Funds) ..> (Upload Receipt) : <<include>>
    (Verify Bank & Disburse Funds) ..> (Notify Student) : <<include>>
    (Record Disbursements) ..> (Notify Student) : <<include>>
```

## Reviewer

```mermaid
usecaseDiagram
    actor Reviewer

    Reviewer --> (Login)
    Reviewer --> (Logout)
    Reviewer --> (View Dashboard)
    Reviewer --> (Evaluate Applications)
    Reviewer --> (View Application Status)
    Reviewer --> (View Notifications)
    Reviewer --> (Manage Profile)
    (Evaluate Applications) ..> (Login) : <<include>>
```

# Flow Diagram (Application to Disbursement)

```mermaid
flowchart TD
    A[Student registers & logs in] --> B[Student browses schemes]
    B --> C{Is student eligible?}
    C -- No --> B
    C -- Yes --> D[Student applies to scheme]
    D --> E[Application status: Submitted]
    E --> F[Reviewer evaluates application]
    F --> G{Reviewer recommendation}
    G -- Not Recommended --> H[Admin reviews]
    G -- Recommended --> H
    H --> I{Admin decision}
    I -- Rejected --> J[Student notified: Rejected]
    I -- Approved --> K[Student notified: Approved]
    K --> L[Student submits bank details]
    L --> M[Admin verifies bank + uploads receipt]
    M --> N{Are bank details valid?}
    N -- No --> O[Application Rejected]
    N -- Yes --> P[Payment record created: First Semester]
    P --> Q[Student notified: Funds Released]
    Q --> R[Application marked Paid]
    R --> S[Student downloads receipt]
    S --> T{Next semester?}
    T -- Yes --> U[Admin records disbursement manually]
    U --> Q
    T -- No --> V[End]
    J --> V
    O --> V
```

# Class Diagram

```mermaid
classDiagram
    class Student {
        +int id
        +string roll_no
        +string name
        +string email
        +string phone
        +string password
        +string gender
        +string address
        +apply() void
        +submitBankDetails() void
        +downloadReceipt() void
        +sendMessage() void
    }
    class Admin {
        +int id
        +string name
        +string email
        +string password
        +manageSchemes() void
        +manageReviewers() void
        +approveRejectApplication() void
        +verifyBank() void
        +recordDisbursement() void
    }
    class Reviewer {
        +int id
        +string name
        +string department
        +string email
        +string password
        +evaluateApplication() void
    }
    class Scheme {
        +int id
        +string scheme_name
        +string amount
        +date deadline
        +enum status
        +text description
    }
    class Application {
        +int id
        +int student_id
        +int scheme_id
        +string application_no
        +decimal family_income
        +date apply_date
        +enum status
        +enum payment_status
        +text reason
    }
    class ScholarshipRecipient {
        +int id
        +int application_id
        +year start_year
    }
    class BankDetail {
        +int id
        +int student_id
        +string bank_name
        +string account_number
        +string account_holder
        +boolean is_verified
    }
    class Receipt {
        +int id
        +int student_id
        +int application_id
        +string filename
    }
    class PaymentRecord {
        +int id
        +int recipient_id
        +int bank_id
        +decimal amount
        +string academic_year
        +string semester
        +date payment_date
    }
    class Notification {
        +int id
        +int student_id
        +int admin_id
        +int reviewer_id
        +string title
        +text message
        +boolean is_read
    }
    class ContactMessage {
        +int id
        +int student_id
        +string full_name
        +string email
        +string subject
        +text message
    }
    class ApplicationReview {
        +int id
        +int application_id
        +int reviewer_id
        +enum recommendation
        +text remarks
    }

    Student "1" --> "0..*" Application : applies
    Scheme "1" --> "0..*" Application : receives
    Admin "1" --> "0..*" Application : approves
    Application "1" --> "0..1" ScholarshipRecipient : becomes
    Student "1" --> "0..*" BankDetail : has
    Student "1" --> "0..*" Receipt : owns
    Application "1" --> "0..*" Receipt : has
    Admin "1" --> "0..*" Receipt : uploads
    ScholarshipRecipient "1" --> "0..*" PaymentRecord : receives
    BankDetail "1" --> "0..*" PaymentRecord : pays to
    Reviewer "1" --> "0..*" ApplicationReview : gives
    Application "1" --> "0..*" ApplicationReview : gets
    Student "1" --> "0..*" Notification : receives
    Admin "1" --> "0..*" Notification : receives
    Reviewer "1" --> "0..*" Notification : receives
    Student "1" --> "0..*" ContactMessage : sends
    Reviewer "0..*" --> "0..*" Scheme : assigned
```
