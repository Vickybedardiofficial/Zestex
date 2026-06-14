# 🎯 ZESTEX Plus - React Native App Integration Documentation

**App Purpose:** Complete Feature & API Documentation for React Native Mobile App  
**Author:** Vicky Bedardi Yadav  
**Framework:** Laravel 11 + Vue JS 3/4  
**Mobile App:** React Native  
**License:** Commercial ($299)

---

## 📑 Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [API Authentication](#api-authentication)
4. [Complete API Endpoints](#complete-api-endpoints)
5. [Feature Modules & Buttons](#feature-modules--buttons)
6. [Database Models](#database-models)
7. [React Native Implementation Guide](#react-native-implementation-guide)
8. [Installation & Setup](#installation--setup)

---

## 🏢 Project Overview

**Zestex Plus** is a robust social media platform built with:
- Backend: Laravel 11 (PHP)
- Frontend: Vue JS 3/4 with Tailwind CSS
- Mobile App: React Native
- Real-time: Laravel Reverb & Pusher.js
- Payments: Stripe Integration
- Live Streaming: Video/Audio Support

### Key Features:
✅ Social Timeline & Posts  
✅ User Profiles & Followers  
✅ Real-time Messaging (Chat)  
✅ Stories (24-hour auto-delete)  
✅ Live Streaming  
✅ Marketplace & E-commerce  
✅ Wallet & Payment System  
✅ Job Listings  
✅ Notifications  
✅ AI Assistant  
✅ Trending & Search  
✅ Bookmarks & Reactions  

---

## 🛠️ Technology Stack

### Backend:
```
Laravel 11
Laravel Sanctum (API Authentication)
Laravel Horizon (Queue Management)
Laravel Reverb (Real-time)
Stripe (Payments)
FFmpeg (Video Processing)
```

### Frontend:
```
Vue.js 3.5.12
Vite 5.4.9
Tailwind CSS 4.1.8
PrimeVue 4.1.1
Axios 1.7.7
```

### Mobile:
```
React Native
Axios (HTTP Client)
Redux/Pinia (State Management)
React Router (Navigation)
```

### Database & Services:
```
Database: Laravel Compatible (MySQL/PostgreSQL)
Cache: Predis (Redis)
Queue: Laravel Horizon
WebSocket: Pusher.js & Laravel Reverb
File Storage: Local/Cloud Storage
```

---

## 🔐 API Authentication

### Authentication Method: **Laravel Sanctum (Token-Based)**

#### Endpoint: `/api/sanctum/token`
```
Method: POST
Content-Type: application/json

REQUEST BODY:
{
  "email": "user@example.com",
  "password": "your_password",
  "device_name": "iPhone 14"
}

RESPONSE:
{
  "token": "1|abc123xyz..."
}
```

#### Usage in React Native:
```javascript
const API_BASE_URL = 'https://your-domain.com/api';

const login = async (email, password) => {
  try {
    const response = await axios.post(`${API_BASE_URL}/sanctum/token`, {
      email,
      password,
      device_name: 'React Native App'
    });
    
    const token = response.data;
    // Store token in secure storage
    await SecureStore.setItemAsync('auth_token', token);
    return token;
  } catch (error) {
    console.error('Login failed:', error.response.data);
  }
};

// For all authenticated requests:
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

---

## 📡 Complete API Endpoints

### 1️⃣ **Authentication APIs**

#### Login / Token Generation
```
POST /api/sanctum/token
├─ Email (required)
├─ Password (required)
└─ Device Name (required)
Response: Bearer Token
```

#### Logout
```
POST /api/auth/logout
├─ Authorization: Bearer Token
└─ Response: Success Message
```

---

### 2️⃣ **Bootstrap APIs**

#### Get Initial App Data (User Dashboard)
```
GET /api/bootstrap/bootstrap
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ User Profile Data
│  ├─ Notifications Count
│  ├─ App Settings
│  ├─ Language Settings
│  └─ Feature Flags
└─ Rate Limit: 60/1min
```

#### Public Bootstrap (Guest User)
```
GET /api/public/bootstrap
├─ Auth: Not Required
├─ Returns:
│  ├─ App Metadata
│  ├─ Trending Topics
│  ├─ Public Settings
│  └─ Feature Flags
└─ Rate Limit: 60/1min
```

---

### 3️⃣ **Timeline & Posts APIs**

#### Get User Feed (Main Timeline)
```
GET /api/timeline/feed
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  ├─ limit (optional, default: 20)
│  └─ sort_by (optional: latest, trending)
├─ Returns:
│  ├─ Array of Posts
│  ├─ Post ID (hashId)
│  ├─ Author Info
│  ├─ Content (text, media, links)
│  ├─ Reactions Count
│  ├─ Comments Count
│  ├─ Reposts Count
│  └─ User's Reaction Status
└─ Rate Limit: 240/1min
```

#### Get Feed Updates (Real-time)
```
GET /api/timeline/update
├─ Auth: Required (Bearer Token)
├─ Returns: New posts since last check
└─ Rate Limit: 240/1min
```

#### Get Post Details
```
GET /api/timeline/post/{hashId}
├─ Auth: Required (Bearer Token)
├─ Path Parameters: hashId (post ID)
├─ Returns:
│  ├─ Post Content
│  ├─ Author Details
│  ├─ Media Files
│  ├─ Engagement Data
│  └─ Comments Preview
└─ Rate Limit: 240/1min
```

#### Get Post Comments
```
GET /api/timeline/post/{hashId}/comments
├─ Auth: Required (Bearer Token)
├─ Path Parameters: hashId (post ID)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Comments
│  ├─ Comment Text
│  ├─ Comment Author
│  ├─ Comment Timestamp
│  └─ Comment Reactions
└─ Rate Limit: 240/1min
```

#### Create Post
```
POST /api/post/editor/create
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Request Body:
│  ├─ content (text)
│  ├─ media[] (images, videos, documents)
│  ├─ location (optional)
│  ├─ visibility (public, friends, private)
│  ├─ tags[] (hashtags)
│  ├─ mentions[] (user mentions)
│  └─ schedule_at (optional)
├─ Returns:
│  ├─ Post ID
│  ├─ Created Timestamp
│  └─ Post URL
└─ Rate Limit: 240/1min
```

#### Delete Post
```
DELETE /api/timeline/post/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ post_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 240/1min
```

#### Add Post Reaction
```
POST /api/timeline/post/reaction/add
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ post_id (hashId)
│  └─ reaction_type (like, love, laugh, wow, sad, angry)
├─ Returns:
│  ├─ Reaction Status
│  └─ Total Reactions Count
└─ Rate Limit: 240/1min
```

#### Toggle Repost
```
POST /api/timeline/post/repost/toggle
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ post_id (hashId)
├─ Returns:
│  ├─ Repost Status (true/false)
│  └─ Repost Count
└─ Rate Limit: 240/1min
```

#### Bookmark Post
```
POST /api/timeline/post/bookmarks/add
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ post_id (hashId)
├─ Returns:
│  ├─ Bookmark Status (true/false)
│  └─ Bookmark Count
└─ Rate Limit: 240/1min
```

#### Vote on Poll
```
POST /api/timeline/post/poll/vote
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ poll_id (hashId)
│  └─ option_id (poll option)
├─ Returns:
│  ├─ Vote Status
│  ├─ Updated Poll Results
│  └─ Vote Count
└─ Rate Limit: 240/1min
```

#### Create Comment
```
POST /api/timeline/post/comment/create
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ post_id (hashId)
│  ├─ comment_text (required)
│  └─ media[] (optional)
├─ Returns:
│  ├─ Comment ID
│  ├─ Created Timestamp
│  └─ Comment URL
└─ Rate Limit: 240/1min
```

#### Delete Comment
```
DELETE /api/timeline/post/comment/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ comment_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 240/1min
```

#### Add Comment Reaction
```
POST /api/timeline/comment/reaction/add
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ comment_id (hashId)
│  └─ reaction_type
├─ Returns:
│  ├─ Reaction Status
│  └─ Total Reactions Count
└─ Rate Limit: 240/1min
```

#### Get Draft Post
```
GET /api/post/editor/draft
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ Draft Post Content
│  ├─ Media Files
│  └─ Timestamp
└─ Rate Limit: 240/1min
```

#### Upload Post Image
```
POST /api/post/editor/media/image/upload
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Form Data:
│  └─ image (file)
├─ Returns:
│  ├─ Image ID
│  ├─ Image URL
│  └─ Thumbnail URL
└─ Rate Limit: 240/1min
```

#### Upload Post Video
```
POST /api/post/editor/media/video/upload
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Form Data:
│  ├─ video (file)
│  ├─ thumbnail (optional)
│  └─ duration (optional)
├─ Returns:
│  ├─ Video ID
│  ├─ Video URL
│  ├─ Thumbnail URL
│  └─ Duration
└─ Rate Limit: 240/1min
```

#### Upload Post Audio
```
POST /api/post/editor/media/audio/upload
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Form Data:
│  ├─ audio (file)
│  └─ duration (optional)
├─ Returns:
│  ├─ Audio ID
│  ├─ Audio URL
│  └─ Duration
└─ Rate Limit: 240/1min
```

#### Upload Post Document
```
POST /api/post/editor/media/document/upload
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Form Data:
│  └─ document (file: pdf, doc, docx, txt)
├─ Returns:
│  ├─ Document ID
│  ├─ Document URL
│  └─ Document Name
└─ Rate Limit: 240/1min
```

#### Delete Media
```
DELETE /api/post/editor/media/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ media_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 240/1min
```

#### Create Poll
```
POST /api/post/editor/poll/create
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ options[] (poll options array)
│  └─ duration (minutes, 15-1440)
├─ Returns:
│  ├─ Poll ID
│  ├─ Poll Options
│  └─ Expires At
└─ Rate Limit: 240/1min
```

#### Delete Poll
```
DELETE /api/post/editor/poll/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ poll_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 240/1min
```

#### Create GIF
```
POST /api/post/editor/gif/create
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ gif_url (URL from Giphy API)
├─ Returns:
│  ├─ GIF ID
│  └─ GIF URL
└─ Rate Limit: 240/1min
```

#### Preview Link
```
POST /api/post/editor/link/preview
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ url (link to preview)
├─ Returns:
│  ├─ Title
│  ├─ Description
│  ├─ Image (thumbnail)
│  └─ Domain
└─ Rate Limit: 240/1min
```

#### Delete Link Snapshot
```
DELETE /api/post/editor/link/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ snapshot_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 240/1min
```

---

### 4️⃣ **Stories APIs**

#### Get Stories Feed
```
GET /api/stories/feed
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ Array of Users
│  ├─ User Stories (24hr)
│  ├─ Viewed Status
│  └─ View Count
└─ Rate Limit: 60/1min
```

#### Get Specific User Stories
```
GET /api/stories/stories/{storyId}
├─ Auth: Required (Bearer Token)
├─ Path Parameters: storyId (user ID)
├─ Returns:
│  ├─ Story Frames
│  ├─ Story Media
│  ├─ Created At
│  └─ Expires At
└─ Rate Limit: 60/1min
```

#### Get Story Views
```
GET /api/stories/views/{frameId}
├─ Auth: Required (Bearer Token)
├─ Path Parameters: frameId (story frame ID)
├─ Returns:
│  ├─ Array of Viewers
│  ├─ Viewer Profiles
│  ├─ View Timestamps
│  └─ View Count
└─ Rate Limit: 60/1min
```

#### Record Story View
```
POST /api/stories/views/record
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ frame_id (frameId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Create Story (Upload)
```
POST /api/story/editor/create
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Form Data:
│  ├─ media (image/video file)
│  ├─ caption (optional text)
│  ├─ duration (seconds, for images)
│  └─ visibility (public, friends, private)
├─ Returns:
│  ├─ Story Frame ID
│  ├─ Story URL
│  └─ Expires At
└─ Rate Limit: 60/1min
```

#### Delete Story
```
DELETE /api/stories/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ story_id (frameId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

---

### 5️⃣ **Profile APIs**

#### Get User Profile
```
GET /api/profile/profile
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  └─ user_id (optional, default: current user)
├─ Returns:
│  ├─ User ID
│  ├─ Username
│  ├─ Display Name
│  ├─ Bio
│  ├─ Avatar URL
│  ├─ Cover Photo
│  ├─ Followers Count
│  ├─ Following Count
│  ├─ Posts Count
│  ├─ Verified Status
│  ├─ Website
│  ├─ Location
│  ├─ Joined Date
│  ├─ Birth Date (if shared)
│  └─ Following Status (for others)
└─ Rate Limit: 60/1min
```

#### Get User Profile Posts
```
GET /api/profile/profile/posts
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ user_id (optional)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of User Posts
│  ├─ Post Content
│  ├─ Post Engagement
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Profile Details
```
GET /api/profile/profile/details
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  └─ user_id (optional)
├─ Returns:
│  ├─ Detailed User Info
│  ├─ Account Settings
│  ├─ Privacy Settings
│  ├─ Verified Badge
│  └─ Account Status
└─ Rate Limit: 60/1min
```

#### Get Profile Followers
```
GET /api/profile/profile/followers
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ user_id (optional)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Followers
│  ├─ Follower Profiles
│  ├─ Follow Status
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Profile Followings
```
GET /api/profile/profile/followings
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ user_id (optional)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Following Users
│  ├─ Following Profiles
│  ├─ Mutual Followers
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Public Profile (Guest View)
```
GET /api/public/profile/
├─ Auth: Not Required
├─ Query Parameters:
│  └─ username (required)
├─ Returns:
│  ├─ Public Profile Data
│  ├─ Public Posts
│  └─ Limited User Info
└─ Rate Limit: 60/1min
```

#### Update Profile
```
PUT /api/settings/profile/update
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Request Body:
│  ├─ display_name (optional)
│  ├─ bio (optional)
│  ├─ avatar (optional, image file)
│  ├─ cover (optional, image file)
│  ├─ website (optional)
│  ├─ location (optional)
│  ├─ birth_date (optional)
│  └─ privacy_level (optional)
├─ Returns:
│  ├─ Updated Profile
│  └─ Success Message
└─ Rate Limit: 60/1min
```

---

### 6️⃣ **Messaging/Chat APIs**

#### Get All Chats
```
GET /api/messenger/chats
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Chat Conversations
│  ├─ Chat Participant(s)
│  ├─ Last Message
│  ├─ Timestamp
│  ├─ Unread Count
│  └─ Chat Avatar
└─ Rate Limit: 60/1min
```

#### Get Chat Archive
```
GET /api/messenger/archive
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Archived Chats
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Unread Chat Count
```
GET /api/messenger/unread/count
├─ Auth: Required (Bearer Token)
├─ Returns:
│  └─ Unread Message Count
└─ Rate Limit: 60/1min
```

#### Create Chat (1-on-1)
```
POST /api/messenger/chats/create
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ user_id (hashId of recipient)
├─ Returns:
│  ├─ Chat ID
│  ├─ Participant Info
│  └─ Success Message
└─ Rate Limit: 60/1min
```

#### Launch Chat
```
POST /api/messenger/chats/launch
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ chat_id (hashId)
├─ Returns:
│  ├─ Chat Data
│  └─ Messages
└─ Rate Limit: 60/1min
```

#### Send Message
```
POST /api/messenger/send
├─ Auth: Required (Bearer Token)
├─ Content-Type: multipart/form-data
├─ Request Body:
│  ├─ chat_id (hashId)
│  ├─ message_text (optional)
│  ├─ attachments[] (optional, files)
│  └─ reply_to (optional, message_id)
├─ Returns:
│  ├─ Message ID
│  ├─ Message Content
│  ├─ Timestamp
│  └─ Delivery Status
└─ Rate Limit: 60/1min
```

#### Get Chat Data
```
GET /api/messenger/chat/{chatId}
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns:
│  ├─ Chat Details
│  ├─ Participants
│  ├─ Last Activity
│  └─ Chat Settings
└─ Rate Limit: 60/1min
```

#### Get Chat Messages
```
GET /api/messenger/chat/{chatId}/messages
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Messages
│  ├─ Message Content
│  ├─ Sender Info
│  ├─ Timestamp
│  ├─ Delivery Status
│  └─ Read Status
└─ Rate Limit: 60/1min
```

#### Add Message Reaction
```
POST /api/messenger/chat/message/add-reaction
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ message_id (hashId)
│  └─ reaction_type (emoji)
├─ Returns:
│  ├─ Reaction Status
│  └─ Total Reactions
└─ Rate Limit: 60/1min
```

#### Delete Message
```
DELETE /api/messenger/chat/message/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ message_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Mark Chat as Read
```
GET /api/messenger/chat/{chatId}/read
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Delete Chat
```
DELETE /api/messenger/chat/{chatId}/delete
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Archive Chat
```
DELETE /api/messenger/chat/{chatId}/archive
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Unarchive Chat
```
DELETE /api/messenger/chat/{chatId}/unarchive
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Clear Conversation
```
DELETE /api/messenger/chat/{chatId}/clear
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (chat ID)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Create Group Chat
```
POST /api/messenger/groups/create
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ group_name (required)
│  ├─ description (optional)
│  ├─ avatar (optional)
│  └─ members[] (user_ids)
├─ Returns:
│  ├─ Group ID
│  ├─ Group Info
│  └─ Members List
└─ Rate Limit: 60/1min
```

#### Get Group Participants
```
GET /api/messenger/groups/{chatId}/participants
├─ Auth: Required (Bearer Token)
├─ Path Parameters: chatId (group ID)
├─ Returns:
│  ├─ Array of Members
│  ├─ Member Roles
│  ├─ Join Dates
│  └─ Member Status
└─ Rate Limit: 60/1min
```

#### Update Group
```
POST /api/messenger/groups/update
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ group_id (hashId)
│  ├─ group_name (optional)
│  ├─ description (optional)
│  └─ avatar (optional)
├─ Returns:
│  ├─ Updated Group Info
│  └─ Success Message
└─ Rate Limit: 60/1min
```

#### Invite Members to Group
```
POST /api/messenger/groups/invite/send
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ group_id (hashId)
│  └─ user_ids[] (array)
├─ Returns:
│  ├─ Invitation Status
│  └─ Invited Members
└─ Rate Limit: 60/1min
```

#### Accept Group Invite
```
POST /api/messenger/groups/invite/accept
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ group_id (hashId)
├─ Returns:
│  ├─ Acceptance Status
│  └─ Group Info
└─ Rate Limit: 60/1min
```

#### Leave Group
```
POST /api/messenger/groups/leave
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ group_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

#### Delete Group
```
DELETE /api/messenger/groups/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ group_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

---

### 7️⃣ **Follow/Unfollow APIs**

#### Follow User
```
POST /api/follows/follow/user
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ user_id (hashId)
├─ Returns:
│  ├─ Follow Status (followed/pending)
│  └─ User Info
└─ Rate Limit: 60/1min
```

#### Accept Follow Request
```
POST /api/follows/accept/user
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ user_id (hashId)
├─ Returns:
│  ├─ Request Status (accepted)
│  └─ User Info
└─ Rate Limit: 60/1min
```

---

### 8️⃣ **Notifications APIs**

#### Get All Notifications
```
GET /api/notifications/all
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Notifications
│  ├─ Notification Type (like, comment, follow, etc.)
│  ├─ Actor Info
│  ├─ Related Item
│  ├─ Timestamp
│  └─ Read Status
└─ Rate Limit: 60/1min
```

#### Get Mentions
```
GET /api/notifications/mentions
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Mentions
│  ├─ Mention Context
│  └─ Timestamp
└─ Rate Limit: 60/1min
```

#### Get Important Notifications
```
GET /api/notifications/important
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Important Notifications
│  ├─ Priority Level
│  └─ Action Required
└─ Rate Limit: 60/1min
```

#### Get Unread Count
```
GET /api/notifications/unread/count
├─ Auth: Required (Bearer Token)
├─ Returns:
│  └─ Unread Notification Count
└─ Rate Limit: 60/1min
```

#### Delete Notification
```
DELETE /api/notifications/delete
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ notification_id (hashId)
├─ Returns: Success Message
└─ Rate Limit: 60/1min
```

---

### 9️⃣ **Marketplace/E-commerce APIs**

#### Get Products
```
POST /api/marketplace/products
├─ Auth: Optional (Bearer Token)
├─ Request Body:
│  ├─ page (optional)
│  ├─ limit (optional)
│  ├─ category_id (optional)
│  ├─ search (optional)
│  ├─ sort_by (optional: newest, popular, price_low, price_high)
│  └─ filters (optional)
├─ Returns:
│  ├─ Array of Products
│  ├─ Product ID
│  ├─ Product Name
│  ├─ Description
│  ├─ Price
│  ├─ Images
│  ├─ Seller Info
│  ├─ Rating
│  ├─ Reviews Count
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Product Details
```
GET /api/marketplace/products/{productId}
├─ Auth: Optional (Bearer Token)
├─ Path Parameters: productId (product ID)
├─ Returns:
│  ├─ Full Product Info
│  ├─ Detailed Description
│  ├─ All Images
│  ├─ All Reviews
│  ├─ Seller Profile
│  ├─ Specifications
│  ├─ Stock Status
│  └─ Shipping Info
└─ Rate Limit: 60/1min
```

#### Get Categories
```
GET /api/marketplace/categories
├─ Auth: Not Required
├─ Returns:
│  ├─ Array of Categories
│  ├─ Category ID
│  ├─ Category Name
│  ├─ Icon/Image
│  └─ Product Count
└─ Rate Limit: 60/1min
```

#### Get Marketplace Metadata
```
GET /api/marketplace/metadata
├─ Auth: Not Required
├─ Returns:
│  ├─ Marketplace Settings
│  ├─ Filters Available
│  ├─ Sort Options
│  └─ Payment Methods
└─ Rate Limit: 60/1min
```

#### Get Bookmarks
```
GET /api/marketplace/bookmarks
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Bookmarked Products
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Bookmark Product
```
POST /api/marketplace/bookmarks/add
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  └─ product_id (hashId)
├─ Returns:
│  ├─ Bookmark Status
│  └─ Success Message
└─ Rate Limit: 60/1min
```

---

### 🔟 **Wallet/Payment APIs**

#### Get Wallet Data
```
GET /api/wallet/data
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ Wallet Balance
│  ├─ Total Earnings
│  ├─ Total Spent
│  ├─ Currency
│  ├─ Verified Status
│  └─ Account Status
└─ Rate Limit: 60/1min
```

#### Get Payment Providers
```
GET /api/wallet/payment/providers
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ Array of Payment Methods
│  ├─ Provider Name (Stripe, PayPal, etc.)
│  ├─ Supported Currencies
│  ├─ Fees
│  └─ Status (active/inactive)
└─ Rate Limit: 60/1min
```

#### Create Deposit
```
POST /api/wallet/deposit
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ amount (required)
│  ├─ currency (default: USD)
│  ├─ payment_provider (stripe, paypal)
│  └─ payment_method (optional)
├─ Returns:
│  ├─ Payment Intent ID
│  ├─ Client Secret
│  ├─ Amount
│  └─ Payment URL
└─ Rate Limit: 60/1min
```

#### Make Transfer
```
POST /api/wallet/transfer
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ receiver_id (hashId)
│  ├─ amount (required)
│  ├─ note (optional)
│  └─ is_gift (optional)
├─ Returns:
│  ├─ Transfer ID
│  ├─ Status
│  ├─ Timestamp
│  └─ Balance Updated
└─ Rate Limit: 60/1min
```

#### Get Transactions
```
GET /api/wallet/transactions
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  ├─ limit (optional)
│  ├─ type (optional: deposit, withdrawal, transfer)
│  └─ date_from, date_to (optional)
├─ Returns:
│  ├─ Array of Transactions
│  ├─ Transaction ID
│  ├─ Type
│  ├─ Amount
│  ├─ Status
│  ├─ Timestamp
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Find Receiver
```
GET /api/wallet/receiver/find
├─ Auth: Required (Bearer Token)
├─ Query Parameters:
│  └─ search (username or email)
├─ Returns:
│  ├─ Array of Users
│  ├─ User ID
│  ├─ Username
│  ├─ Avatar
│  └─ Verified Status
└─ Rate Limit: 60/1min
```

#### Get Receiver History
```
GET /api/wallet/receiver/history
├─ Auth: Required (Bearer Token)
├─ Returns:
│  ├─ Array of Recent Receivers
│  ├─ Receiver Info
│  ├─ Last Transfer Date
│  └─ Total Transfers
└─ Rate Limit: 60/1min
```

---

### 1️⃣1️⃣ **Search APIs**

#### Search Posts
```
GET /api/public/search/posts
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ q (search query)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Posts
│  ├─ Relevance Score
│  ├─ Highlight Matches
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Search People
```
GET /api/public/search/people
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ q (search query)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Users
│  ├─ User Profile
│  ├─ Follower Count
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Search Hashtags
```
GET /api/public/search/hashtags
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ q (search query)
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Hashtags
│  ├─ Hashtag Name
│  ├─ Post Count
│  ├─ Trend Status
│  └─ Pagination
└─ Rate Limit: 60/1min
```

---

### 1️⃣2️⃣ **Explore APIs**

#### Get Explore Posts
```
GET /api/public/explore/posts
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ page (optional)
│  ├─ limit (optional)
│  └─ category (optional)
├─ Returns:
│  ├─ Array of Trending Posts
│  ├─ Post Content
│  ├─ Engagement Metrics
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Explore People
```
GET /api/public/explore/people
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ page (optional)
│  └─ limit (optional)
├─ Returns:
│  ├─ Array of Suggested Users
│  ├─ User Profile
│  ├─ Follower Count
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get Trending Topics
```
GET /api/public/explore/trending/topics
├─ Auth: Not Required
├─ Returns:
│  ├─ Array of Trending Hashtags
│  ├─ Hashtag Name
│  ├─ Post Count
│  ├─ Trend Rank
│  └─ Category
└─ Rate Limit: 60/1min
```

#### Get Trending Today
```
GET /api/public/explore/trending/today
├─ Auth: Not Required
├─ Returns:
│  ├─ Today's Trending
│  ├─ Top Posts
│  ├─ Top Users
│  ├─ Top Hashtags
│  └─ Timestamp
└─ Rate Limit: 60/1min
```

#### Get All Trending
```
GET /api/public/explore/trending/all
├─ Auth: Not Required
├─ Returns:
│  ├─ All Trending Topics
│  ├─ Trend History
│  ├─ Trend Growth
│  └─ Top Categories
└─ Rate Limit: 60/1min
```

#### Get Explore News
```
GET /api/public/explore/news
├─ Auth: Not Required
├─ Query Parameters:
│  ├─ page (optional)
│  ├─ limit (optional)
│  └─ category (optional)
├─ Returns:
│  ├─ Array of News Items
│  ├─ News Title
│  ├─ Description
│  ├─ Image
│  ├─ Source
│  ├─ Published Date
│  └─ Pagination
└─ Rate Limit: 60/1min
```

#### Get News Item
```
GET /api/public/explore/news/item
├─ Auth: Not Required
├─ Query Parameters:
│  └─ news_id (required)
├─ Returns:
│  ├─ Full News Content
│  ├─ Related News
│  ├─ Comments
│  └─ Engagement Data
└─ Rate Limit: 60/1min
```

---

### 1️⃣3️⃣ **Jobs APIs**

#### Get Jobs Listing
```
GET /api/jobs
├─ Auth: Optional (Bearer Token)
├─ Query Parameters:
│  ├─ page (optional)
│  ├─ limit (optional)
│  ├─ category (optional)
│  ├─ location (optional)
│  ├─ salary_min (optional)
│  └─ sort_by (optional)
├─ Returns:
│  ├─ Array of Job Listings
│  ├─ Job ID
│  ├─ Job Title
│  ├─ Company
│  ├─ Description
│  ├─ Salary
│  ├─ Location
│  ├─ Job Type
│  ├─ Posted Date
│  └─ Pagination
└─ Rate Limit: 60/1min
```

---

### 1️⃣4️⃣ **AI Assistant APIs**

#### AI Content Generation
```
POST /api/ai/generate
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ prompt (text request)
│  ├─ type (caption, content, hashtags, etc.)
│  └─ tone (formal, casual, professional)
├─ Returns:
│  ├─ Generated Content
│  ├─ Suggestions
│  └─ Timestamp
└─ Rate Limit: 120/1min
```

#### AI Translation
```
POST /api/translator
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ text (content to translate)
│  ├─ from_lang (source language)
│  └─ to_lang (target language)
├─ Returns:
│  ├─ Translated Text
│  └─ Language Info
└─ Rate Limit: 60/1min
```

---

### 1️⃣5️⃣ **Account Settings APIs**

#### Update Profile Settings
```
PUT /api/settings/profile/update
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ display_name (optional)
│  ├─ bio (optional)
│  ├─ avatar (optional)
│  ├─ cover (optional)
│  └─ ... (other fields)
├─ Returns:
│  ├─ Updated Profile
│  └─ Success Message
└─ Rate Limit: 60/1min
```

#### Update Privacy Settings
```
PUT /api/settings/privacy/update
├─ Auth: Required (Bearer Token)
├─ Request Body:
│  ├─ is_private (boolean)
│  ├─ allow_messages_from (all, followers, none)
│  ├─ allow_tags (boolean)
│  └─ allow_search (boolean)
├─ Returns:
│  ├─ Updated Settings
│  └─ Success Message
└─ Rate Limit: 60/1min
```

---

## 🎨 Feature Modules & Buttons

### **1. Authentication Module**

**Buttons:**
- 🔑 Login
- 📝 Sign Up
- 👁️ Show/Hide Password
- 🔐 Forgot Password
- ↩️ Back to Login
- 📱 Login with Phone
- 🔗 Social Login

**States:**
- Loading
- Error
- Success
- Input Validation

---

### **2. Timeline/Feed Module**

**Buttons:**
- ➕ Create New Post
- 💬 Comment
- ❤️ Like/React
- 🔄 Repost/Share
- 📌 Bookmark
- ⋮ More Options (Edit/Delete)
- 🖼️ View Media
- 📖 Read More/Less
- 🔗 Share Link
- 👤 View Profile

**Features:**
- Post composition with rich text editor
- Media picker (images, videos, GIFs)
- Emoji picker
- Mention suggestions
- Hashtag suggestions
- Poll creation
- Link preview
- Real-time updates

---

### **3. Post Editor Module**

**Buttons:**
- 📸 Add Image
- 🎥 Add Video
- 🎵 Add Audio
- 📄 Add Document
- 🎬 Add GIF
- 📊 Create Poll
- 🔗 Add Link
- 📍 Add Location
- 👤 Mention Users
- #️⃣ Add Hashtags
- 📤 Post/Publish
- 💾 Save as Draft
- ✖️ Discard
- 🕐 Schedule Post

---

### **4. Stories Module**

**Buttons:**
- ➕ Create Story
- 👁️ View Story
- ⏭️ Next Story
- ⏮️ Previous Story
- 📊 View Story Viewers
- ❤️ React to Story
- 💬 Reply to Story
- 🗑️ Delete Story
- 👀 Mark as Viewed

**Features:**
- 24-hour auto-delete
- Multiple media support
- Viewer list
- Reactions

---

### **5. Profile Module**

**Buttons:**
- ✏️ Edit Profile
- 👥 Follow/Unfollow
- 💬 Message
- 🔗 Share Profile
- 👀 View Profile
- 📋 View Posts
- 👥 View Followers
- 👥 View Following
- ⚙️ Profile Settings
- 🚫 Block User
- 📢 Report User

**Features:**
- Profile picture
- Cover photo
- Bio
- Stats (followers, following, posts)
- Verified badge
- Links (website)
- Join date

---

### **6. Messaging Module**

**Buttons:**
- ➕ New Chat
- 💬 Send Message
- 📎 Attach File
- 🎥 Send Video Message
- 🎵 Send Audio Message
- 📸 Send Photo
- ❤️ React to Message
- ⋮ More Options
- 🗑️ Delete Message
- ✓ Mark as Read
- 🔍 Search Chat
- 👥 Add to Group
- ✖️ Delete Chat
- 📦 Archive Chat

**Features:**
- 1-on-1 messaging
- Group chats
- File sharing
- Message reactions
- Message search
- Chat history
- Read receipts
- Typing indicators

---

### **7. Notification Module**

**Buttons:**
- 🔔 Notifications
- ✓ Mark as Read
- 🗑️ Delete Notification
- ❤️ Like Notification
- 💬 Comment Notification
- 👥 Follow Notification
- 📤 Repost Notification
- 📍 Mention Notification

**Features:**
- Real-time notifications
- Notification types
- Notification filtering
- Read/Unread status
- Clear all

---

### **8. Marketplace Module**

**Buttons:**
- 🛍️ Browse Products
- 🔍 Search Products
- 🏷️ Filter by Category
- 💰 Sort by Price
- ⭐ View Rating
- 📌 Bookmark Product
- 🛒 Add to Cart
- 💳 Checkout
- ❤️ Add to Wishlist
- 📸 View Photos
- 💬 Read Reviews
- 🖊️ Write Review
- ⬅️ Back to Shopping

---

### **9. Wallet Module**

**Buttons:**
- 💰 View Balance
- 💵 Deposit Money
- 📤 Withdraw Money
- 📤 Transfer Money
- 📋 View Transactions
- 💳 Select Payment Method
- ✓ Confirm Payment
- 🔍 Search Recipient
- 📜 Transaction History

**Features:**
- Wallet balance display
- Transaction history
- Payment methods
- Money transfer
- Deposit/Withdrawal

---

### **10. Settings Module**

**Buttons:**
- ⚙️ Settings
- 👤 Account
- 🔒 Privacy
- 🔔 Notifications
- 🌙 Dark Mode
- 🌍 Language
- 📱 Devices
- 🔐 Change Password
- 🗑️ Delete Account
- 📞 Help & Support
- ℹ️ About App
- ✖️ Close Settings

---

## 🗄️ Database Models

```
User
├── id (UUID)
├── username (unique)
├── email (unique)
├── password (hashed)
├── display_name
├── avatar
├── cover_photo
├── bio
├── website
├── location
├── birth_date
├── is_private
├── is_verified
├── followers_count
├── following_count
├── posts_count
├── created_at
└── updated_at

Post
├── id (UUID)
├── user_id (FK)
├── content (text)
├── media (relations)
├── mentions (array)
├── hashtags (array)
├── location
├── visibility (public/friends/private)
├── comments_count
├── reactions_count
├── reposts_count
├── bookmarks_count
├── is_edited
├── created_at
└── updated_at

Comment
├── id (UUID)
├── post_id (FK)
├── user_id (FK)
├── content (text)
├── media (relations)
├── reactions_count
├── created_at
└── updated_at

Message
├── id (UUID)
├── chat_id (FK)
├── sender_id (FK)
├── content (text)
├── attachments (relations)
├── is_read
├── reactions (array)
├── created_at
└── updated_at

Chat
├── id (UUID)
├── participants (array)
├── last_message_id (FK)
├── last_activity_at
├── is_archived
├── created_at
└── updated_at

Story
├── id (UUID)
├── user_id (FK)
├── media (relation)
├── caption
├── viewers (array)
├── reactions (array)
├── expires_at (24hrs)
├── created_at
└── updated_at

Product
├── id (UUID)
├── seller_id (FK)
├── name
├── description
├── price
├── category_id (FK)
├── images (relations)
├── rating
├── reviews_count
├── stock_quantity
├── is_available
├── created_at
└── updated_at

Wallet
├── id (UUID)
├── user_id (FK)
├── balance (decimal)
├── currency
├── total_earned
├── total_spent
├── verified_at
├── created_at
└── updated_at

Notification
├── id (UUID)
├── user_id (FK)
├── actor_id (FK)
├── type
├── related_id (FK)
├── message
├── is_read
├── created_at
└── updated_at

Follower
├── id (UUID)
├── follower_id (FK)
├── following_id (FK)
├── status (active/pending)
├── created_at
└── updated_at
```

---

## 📱 React Native Implementation Guide

### **Installation & Dependencies**

```bash
# Create React Native App
npx react-native init ZestexApp

# Navigate to project
cd ZestexApp

# Install essential packages
npm install axios
npm install @react-navigation/native @react-navigation/bottom-tabs @react-navigation/native-stack
npm install react-native-screens react-native-safe-area-context
npm install react-native-vector-icons
npm install redux react-redux @reduxjs/toolkit
npm install react-native-secure-store
npm install react-native-image-picker
npm install react-native-video
npm install socket.io-client
npm install moment
npm install react-native-toast-message
```

### **API Service Setup**

```javascript
// src/services/api.js
import axios from 'axios';
import * as SecureStore from 'react-native-secure-store';

const API_BASE_URL = 'https://your-domain.com/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
});

// Interceptor to add auth token
api.interceptors.request.use(
  async (config) => {
    const token = await SecureStore.getItemAsync('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export default api;
```

### **Redux Store Setup**

```javascript
// src/store/index.js
import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import postsReducer from './slices/postsSlice';
import messagesReducer from './slices/messagesSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    posts: postsReducer,
    messages: messagesReducer,
  },
});
```

### **Authentication Flow**

```javascript
// src/screens/LoginScreen.js
import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import { useDispatch } from 'react-redux';
import api from '../services/api';
import * as SecureStore from 'react-native-secure-store';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const dispatch = useDispatch();

  const handleLogin = async () => {
    setLoading(true);
    try {
      const response = await api.post('/sanctum/token', {
        email,
        password,
        device_name: 'React Native App',
      });

      const token = response.data;
      await SecureStore.setItemAsync('auth_token', token);

      // Update Redux store
      dispatch(setUser({ authenticated: true }));

      navigation.reset({
        index: 0,
        routes: [{ name: 'Home' }],
      });
    } catch (error) {
      alert('Login failed: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>ZESTEX Plus</Text>
      
      <TextInput
        style={styles.input}
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        editable={!loading}
      />

      <TextInput
        style={styles.input}
        placeholder="Password"
        secureTextEntry
        value={password}
        onChangeText={setPassword}
        editable={!loading}
      />

      <TouchableOpacity
        style={[styles.button, loading && styles.disabledButton]}
        onPress={handleLogin}
        disabled={loading}
      >
        {loading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>Login</Text>
        )}
      </TouchableOpacity>

      <TouchableOpacity onPress={() => navigation.navigate('Signup')}>
        <Text style={styles.link}>Don't have an account? Sign up</Text>
      </TouchableOpacity>
    </View>
  );
}
```

### **Timeline Screen**

```javascript
// src/screens/TimelineScreen.js
import React, { useEffect, useState } from 'react';
import {
  View,
  FlatList,
  ActivityIndicator,
  RefreshControl,
  Text,
} from 'react-native';
import api from '../services/api';
import PostCard from '../components/PostCard';

export default function TimelineScreen() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);

  const fetchFeed = async (pageNum = 1) => {
    try {
      const response = await api.get('/timeline/feed', {
        params: { page: pageNum, limit: 20 },
      });
      setPosts(pageNum === 1 ? response.data : [...posts, ...response.data]);
    } catch (error) {
      console.error('Error fetching feed:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchFeed();
  }, []);

  const handleRefresh = () => {
    setRefreshing(true);
    setPage(1);
    fetchFeed(1);
  };

  const handleLoadMore = () => {
    const nextPage = page + 1;
    setPage(nextPage);
    fetchFeed(nextPage);
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
      </View>
    );
  }

  return (
    <FlatList
      data={posts}
      keyExtractor={(item) => item.id}
      renderItem={({ item }) => <PostCard post={item} />}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={handleRefresh}
        />
      }
      onEndReached={handleLoadMore}
      onEndReachedThreshold={0.3}
    />
  );
}
```

### **Messaging Screen**

```javascript
// src/screens/MessagesScreen.js
import React, { useEffect, useState } from 'react';
import {
  View,
  FlatList,
  TextInput,
  TouchableOpacity,
  Text,
} from 'react-native';
import api from '../services/api';

export default function MessagesScreen({ route }) {
  const { chatId } = route.params;
  const [messages, setMessages] = useState([]);
  const [inputText, setInputText] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchMessages();
  }, [chatId]);

  const fetchMessages = async () => {
    try {
      const response = await api.get(`/messenger/chat/${chatId}/messages`, {
        params: { page: 1, limit: 50 },
      });
      setMessages(response.data);
    } catch (error) {
      console.error('Error fetching messages:', error);
    } finally {
      setLoading(false);
    }
  };

  const sendMessage = async () => {
    if (!inputText.trim()) return;

    try {
      await api.post('/messenger/send', {
        chat_id: chatId,
        message_text: inputText,
      });
      setInputText('');
      fetchMessages();
    } catch (error) {
      console.error('Error sending message:', error);
    }
  };

  return (
    <View style={styles.container}>
      <FlatList
        data={messages}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <View style={styles.messageContainer}>
            <Text>{item.content}</Text>
          </View>
        )}
      />

      <View style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          placeholder="Type a message..."
          value={inputText}
          onChangeText={setInputText}
        />
        <TouchableOpacity
          style={styles.sendButton}
          onPress={sendMessage}
        >
          <Text style={styles.sendButtonText}>Send</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}
```

### **Navigation Structure**

```javascript
// src/navigation/RootNavigator.js
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

import LoginScreen from '../screens/LoginScreen';
import SignupScreen from '../screens/SignupScreen';
import TimelineScreen from '../screens/TimelineScreen';
import StoriesScreen from '../screens/StoriesScreen';
import ProfileScreen from '../screens/ProfileScreen';
import MessagesScreen from '../screens/MessagesScreen';
import NotificationsScreen from '../screens/NotificationsScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function AuthNavigator() {
  return (
    <Stack.Navigator>
      <Stack.Screen
        name="Login"
        component={LoginScreen}
        options={{ headerShown: false }}
      />
      <Stack.Screen
        name="Signup"
        component={SignupScreen}
        options={{ title: 'Create Account' }}
      />
    </Stack.Navigator>
  );
}

function HomeNavigator() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ color, size }) => {
          let iconName;
          if (route.name === 'Timeline') iconName = 'home';
          else if (route.name === 'Stories') iconName = 'play-circle';
          else if (route.name === 'Messages') iconName = 'chat';
          else if (route.name === 'Notifications') iconName = 'bell';
          else if (route.name === 'Profile') iconName = 'account';

          return <Icon name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#007AFF',
        tabBarInactiveTintColor: '#999',
      })}
    >
      <Tab.Screen name="Timeline" component={TimelineScreen} />
      <Tab.Screen name="Stories" component={StoriesScreen} />
      <Tab.Screen name="Messages" component={MessagesScreen} />
      <Tab.Screen name="Notifications" component={NotificationsScreen} />
      <Tab.Screen name="Profile" component={ProfileScreen} />
    </Tab.Navigator>
  );
}

export function RootNavigator() {
  const [isLoggedIn, setIsLoggedIn] = React.useState(false);

  React.useEffect(() => {
    // Check if user is logged in
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    const token = await SecureStore.getItemAsync('auth_token');
    setIsLoggedIn(!!token);
  };

  return (
    <NavigationContainer>
      {isLoggedIn ? <HomeNavigator /> : <AuthNavigator />}
    </NavigationContainer>
  );
}
```

---

## ✅ Installation & Setup

### **Step 1: Clone Repository**
```bash
git clone https://github.com/Vickybedardiofficial/Zestex.git
cd Zestex
```

### **Step 2: Backend Setup (Laravel)**
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### **Step 3: React Native App Setup**
```bash
cd ../ZestexApp  # Navigate to React Native app folder
npm install
cd ios && pod install && cd ..
npm run android  # For Android
# OR
npm run ios      # For iOS
```

### **Step 4: Environment Configuration**
Create `.env` file in React Native app root:
```
API_BASE_URL=https://your-backend-url.com/api
STRIPE_KEY=your_stripe_key
GIPHY_API_KEY=your_giphy_key
```

### **Step 5: Run App**
```bash
npm run android
# OR
npm run ios
```

---

## 📊 API Response Examples

### **Login Response:**
```json
{
  "token": "1|abc123xyz456..."
}
```

### **Timeline Post Response:**
```json
{
  "id": "post-123",
  "user_id": "user-456",
  "content": "Post content here",
  "created_at": "2026-06-14T10:30:00Z",
  "reactions": {
    "like": 45,
    "love": 12,
    "laugh": 3
  },
  "comments_count": 8,
  "reposts_count": 5,
  "user": {
    "id": "user-456",
    "username": "john_doe",
    "avatar": "https://example.com/avatar.jpg"
  }
}
```

### **Message Response:**
```json
{
  "id": "msg-789",
  "chat_id": "chat-101",
  "sender_id": "user-456",
  "content": "Hello! How are you?",
  "created_at": "2026-06-14T11:00:00Z",
  "is_read": true
}
```

---

## 🔒 Security Best Practices

1. **Store Token Securely**: Use `react-native-secure-store` for token storage
2. **Use HTTPS**: All API calls must use HTTPS
3. **Validate Input**: Always validate user input before sending to API
4. **Handle Errors**: Implement proper error handling and user feedback
5. **Rate Limiting**: Respect API rate limits
6. **Logout on Token Expiry**: Handle 401 errors and logout user

---

## 📝 Conclusion

This documentation provides a complete guide for developing the ZESTEX Plus mobile app in React Native. All API endpoints, features, and implementation guides are included. Follow this documentation step-by-step for successful app development.

**For Support:**
- Email: vicktbedardi9@gmail.com
- Telegram: https://t.me/vicktbedardi9_contact

---

**Last Updated:** June 14, 2026  
**Version:** 1.0
