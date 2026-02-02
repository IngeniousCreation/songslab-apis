# SongsLab API - Repository Status

## ✅ Repository Ready for Deployment

This repository contains the complete Laravel 11 backend API for SongsLab.

## 📁 What's Included

### Core Application Files
- ✅ All Laravel application code (`app/`)
- ✅ All database migrations (15 migrations)
- ✅ All Eloquent models (7 models)
- ✅ All API controllers (6 controllers)
- ✅ Custom authentication middleware
- ✅ Database seeders (FeedbackTopicsSeeder)
- ✅ Configuration files
- ✅ Routes (API routes configured)

### Controllers
1. `AuthController` - Registration, login, email verification
2. `SongController` - Song CRUD, file uploads, sharing
3. `SoundingBoardController` - Member management, approval flow
4. `FeedbackTopicController` - Feedback topics listing
5. `FeedbackController` - Feedback submission and retrieval
6. `EmailVerificationController` - Email verification flow

### Models
1. `User` - User authentication and profile
2. `Song` - Song management with feedback count accessor
3. `SongFile` - Audio and lyrics file management
4. `Lyrics` - Lyrics content and PDF storage
5. `SoundingBoardMember` - Sounding board member management
6. `FeedbackTopic` - Feedback topic definitions
7. `Feedback` - Feedback submissions

### Database Migrations
1. Users table with profile fields
2. Songs table with share tokens
3. Song files table (audio + lyrics PDFs)
4. Lyrics table (text + file reference)
5. Sounding board members table
6. Feedback topics table
7. Song feedback requests (many-to-many)
8. Feedback table
9. Email verification tokens
10. Personal access tokens (custom auth)
11. Cache, jobs, sessions tables

### Documentation
- ✅ `README.md` - Project overview
- ✅ `DEPLOYMENT.md` - Hostinger deployment guide
- ✅ `DOCKER_SETUP_GUIDE.md` - Local Docker setup
- ✅ `SETUP_SUMMARY.md` - Setup instructions
- ✅ `.env.example` - Environment configuration template

## 🚫 What's NOT Included (Correctly Ignored)

- ❌ `vendor/` - Composer dependencies (install with `composer install`)
- ❌ `node_modules/` - NPM dependencies (install with `npm install`)
- ❌ `.env` - Environment file (copy from `.env.example`)
- ❌ `storage/` uploaded files - User-generated content
- ❌ Temporary/cache files
- ❌ IDE configuration files

## 🔧 Key Features Implemented

### Authentication System
- Custom token-based authentication (NOT Laravel Sanctum)
- Email verification with custom SMTP
- User registration with username, first_name, last_name
- Password reset functionality

### Song Management
- Song upload with audio files
- Multiple audio file versions
- Lyrics upload (text OR PDF)
- Development stage tracking
- Share token generation
- Feedback topic selection during upload

### Sounding Board System
- Invite-based member system
- Email-based identity verification
- Approval workflow (pending → approved/rejected)
- Access control for song viewing

### Feedback System
- 14 predefined feedback topics
- Custom feedback requests (max 2000 chars)
- Individual feedback per topic (max 2000 chars each)
- Visibility control (private/group)
- Feedback count accessor (dynamic calculation)

### File Storage
- Audio file uploads (MP3, WAV, etc.)
- Lyrics PDF uploads
- File versioning for audio files
- Storage symlink for public access

## 📊 Database Schema

**Tables:** 15 total
- users
- songs
- song_files
- lyrics
- sounding_board_members
- feedback_topics
- song_feedback_requests (pivot)
- feedback
- email_verification_tokens
- personal_access_tokens
- cache, cache_locks
- jobs, job_batches, failed_jobs
- sessions

## 🔐 Security Features

- Password hashing (bcrypt)
- Custom token authentication
- Email verification required
- CORS configuration for frontend
- File upload validation
- SQL injection protection (Eloquent ORM)
- XSS protection (Laravel defaults)

## 🌐 API Endpoints

### Authentication
- POST `/api/register` - User registration
- POST `/api/login` - User login
- POST `/api/logout` - User logout
- POST `/api/email/verify` - Send verification email
- POST `/api/email/verify/{token}` - Verify email

### Songs
- GET `/api/songs` - List user's songs
- POST `/api/songs` - Upload new song
- GET `/api/songs/{id}` - Get song details
- PUT `/api/songs/{id}` - Update song
- DELETE `/api/songs/{id}` - Delete song
- GET `/api/songs/public/{shareToken}` - Get song by share token
- GET `/api/songs/{id}/feedback` - Get song feedback (songwriter only)

### Sounding Board
- POST `/api/sounding-board/invite` - Invite member
- GET `/api/sounding-board/{songId}` - List members
- PUT `/api/sounding-board/{memberId}/approve` - Approve member
- PUT `/api/sounding-board/{memberId}/reject` - Reject member

### Feedback
- GET `/api/feedback-topics` - List all feedback topics
- POST `/api/feedback` - Submit feedback

## 🚀 Next Steps

1. **Push to Git Repository**
   ```bash
   git commit -m "Initial commit: SongsLab Laravel API"
   git remote add origin <your-repo-url>
   git push -u origin main
   ```

2. **Deploy to Hostinger**
   - Follow `DEPLOYMENT.md` guide
   - Configure `.env` file
   - Run migrations
   - Set up queue worker

3. **Connect Frontend**
   - Update `FRONTEND_URL` in `.env`
   - Configure CORS settings
   - Test API endpoints

## 📝 Notes

- PHP 8.2+ required (Laravel 11)
- MySQL 8.0+ recommended
- Redis required for queues
- SMTP configured for mail.spacemail.com
- File uploads stored in `storage/app/public`
- Feedback count calculated dynamically (no manual updates needed)

