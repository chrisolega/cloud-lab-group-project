# Campus Navigator

A cloud-based web app (CSBC 252 capstone) that helps new students find buildings and events on their campus. Built with plain PHP, HTML, CSS and MySQL. Designed to deploy on AWS Free Tier: EC2 (Apache + PHP), RDS (MySQL) and S3 (uploaded images).

## Default accounts

| Role | Login | Password |
|---|---|---|
| Admin | admin | admin123 |
| Manager (every school) | gctu_manager, ug_manager, knust_manager, umat_manager, atu_manager, ktu_manager | manager123 |
| Student (GCTU sample) | index number 2425402594 | none needed |

Change these passwords after first login (admins can edit managers from the admin panel).

## Folder structure

```
campus-navigator/
  index.php            landing page with the 6 school cards
  css/style.css        all styling
  config/config.php    ALL secrets and settings (DB + S3) - excluded from Git
  includes/db.php      PDO database connection + session start
  includes/auth.php    role checks (student / manager / admin)
  includes/upload.php  the ONE upload function (S3 or local, switched in config)
  includes/header.php  shared navigation
  includes/footer.php  shared footer
  student/             student login, events page, logout
  manager/             manager login (school first), events CRUD, students CSV/manual, logout
  admin/               admin login, dashboard totals, managers/students/events CRUD
  uploads/             local image storage (development mode only)
  database.sql         full schema + sample data
```

## Run locally on XAMPP

1. Copy the `campus-navigator` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in the XAMPP control panel.
3. Open phpMyAdmin (http://localhost/phpmyadmin), go to Import, and import `database.sql`.
4. Open `config/config.php` and confirm:
   - DB_HOST = localhost, DB_USER = root, DB_PASS = '' (XAMPP defaults)
   - UPLOAD_MODE = 'local'
5. Visit http://localhost/campus-navigator/

## Deploying to AWS (EC2 + RDS + S3)

Only two things change in code: `config/config.php` values and `UPLOAD_MODE`.

### 1. IAM (least privilege)
- Create an IAM role for EC2 with ONLY `AmazonS3FullAccess` scoped to your bucket (or a custom policy allowing s3:PutObject/GetObject on that bucket).
- Attach the role to the EC2 instance. Because a role is attached, the AWS SDK picks up credentials automatically - no keys in code.

### 2. RDS (MySQL)
- Create a MySQL RDS instance (db.t3.micro, Free Tier), no public access.
- Set its security group to allow port 3306 ONLY from the EC2 security group.
- Connect from EC2 (`mysql -h your-rds-endpoint -u admin -p`) and run `database.sql`.

### 3. S3
- Create a bucket, enable public read for the `events/` prefix (or use a bucket policy allowing GetObject on `events/*`).
- Put the bucket name and region in `config/config.php`.

### 4. EC2
- Launch Amazon Linux 2023 or Ubuntu (t2.micro/t3.micro, Free Tier).
- Security group: allow inbound 80, 443 and 22 (22 from your IP only).
- Install Apache, PHP, and the MySQL PHP extension, then upload the project to `/var/www/html/`.
- Install the AWS SDK for PHP in the project root: `composer require aws/aws-sdk-php` (creates the `vendor/` folder used by `includes/upload.php`).

### 5. Switch the config
In `config/config.php` set:
- DB_HOST = your RDS endpoint, DB_USER / DB_PASS = your RDS credentials
- UPLOAD_MODE = 's3'
- S3_BUCKET and S3_REGION to your bucket

Uploaded images now go straight to S3 and are never written to EC2 local storage.

### 6. CloudWatch
- EC2 basic metrics (CPU, network) appear in CloudWatch automatically; create an alarm on CPUUtilization > 80% for the deployment proof screenshots.

### Bonus: Application Load Balancer
- Create a second EC2 instance from an AMI of the first, put both in a target group, and create an ALB forwarding port 80 to the target group. Point users at the ALB DNS name.

## Security notes (matches course requirements)
- Every query uses PDO prepared statements (no SQL injection).
- Manager and admin passwords hashed with password_hash().
- PHP sessions with separate role checks - students cannot open manager pages, managers cannot open admin pages.
- All secrets live in config/config.php, which is in .gitignore and blocked from the web by config/.htaccess.
- Image uploads validated by type and size in ONE function (includes/upload.php).
