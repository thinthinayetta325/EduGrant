CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL
);
CREATE TABLE IF NOT EXISTS student (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT NULL,
    password VARCHAR(100) NOT NULL,
    gender VARCHAR(20),
    address VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_name VARCHAR(100) NOT NULL,
    amount  VARCHAR(100) NOT NULL,
    deadline DATE,
    status ENUM('Active','Closed','Draft') DEFAULT 'Active',
    description TEXT,
    eligibility TEXT,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    scheme_id INT NOT NULL,
    application_no VARCHAR(20) NOT NULL UNIQUE,
    family_income DECIMAL(10,2),
    apply_date DATE,
    status ENUM(
        'Submitted',
        'Under Review',
        'Recommended',
        'Approved',
        'Rejected'
    ) DEFAULT 'Submitted',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    payment_status	enum('Pending', 'Paid'),
    receipt_file	varchar(255),
    father_occupation VARCHAR(100),
    mother_occupation VARCHAR(100),
    grade_10_marks DECIMAL(5,2),
    num_siblings INT DEFAULT 0,
    house_photo VARCHAR(255),
    reason TEXT,
    FOREIGN KEY (student_id) REFERENCES student(id),
    FOREIGN KEY (scheme_id) REFERENCES schemes(id),
    FOREIGN KEY (approved_by) REFERENCES admin(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
CREATE TABLE IF NOT EXISTS scholarship_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    start_year YEAR,

    FOREIGN KEY (application_id)
    REFERENCES applications(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
CREATE TABLE IF NOT EXISTS bank_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bank_name VARCHAR(50),
    account_number VARCHAR(20),
    account_holder VARCHAR(50),
    is_verified BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id)
    REFERENCES student(id)
);

CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    application_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES student(id),
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (uploaded_by) REFERENCES admin(id)
);
CREATE TABLE IF NOT EXISTS payment_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    bank_id BIGINT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    payment_date DATE,

    FOREIGN KEY (recipient_id)
    REFERENCES scholarship_recipients(id),

    FOREIGN KEY (bank_id)
    REFERENCES bank_details(id)
);
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    reviewer_id INT DEFAULT NULL,
    title VARCHAR(100),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    type VARCHAR(50),
    INDEX (admin_id),
    INDEX (reviewer_id)
);
CREATE TABLE IF NOT EXISTS reviewers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    department VARCHAR(100),
    email VARCHAR(50) UNIQUE,
    password VARCHAR(100) NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS reviewer_scheme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheme_id INT NOT NULL,
    reviewer_id INT NOT NULL,

    FOREIGN KEY (scheme_id)
    REFERENCES schemes(id),

    FOREIGN KEY (reviewer_id)
    REFERENCES reviewers(id)
);
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS application_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    recommendation ENUM(
        'Recommended',
        'Not Recommended'
    ),
    remarks TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
    REFERENCES applications(id),

    FOREIGN KEY (reviewer_id)
    REFERENCES reviewers(id)
);
