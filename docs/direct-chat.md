# EGYAKIN Direct Chat — Technical Documentation

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [Conversation Types](#conversation-types)
5. [Real-Time (WebSocket)](#real-time-websocket)
6. [File Handling](#file-handling)
7. [Push Notifications (FCM)](#push-notifications-fcm)
8. [Authentication](#authentication)
9. [API Endpoints](#api-endpoints)
10. [WebSocket Events Reference](#websocket-events-reference)
11. [Client Integration Flow](#client-integration-flow)
12. [Key Design Decisions](#key-design-decisions)

---

## Overview

The DirectChat module provides real-time human-to-human messaging for EGYAKIN. It is **completely separate** from the existing AI consultation chat (`app/Modules/Chat/`).

**Supports:**
- Private 1-to-1 messaging (doctor ↔ doctor, doctor ↔ user)
- Case groups (doctors-only, invite-based)
- Social groups (open join)
- Text, image, voice, and file messages
- Emoji reactions, reply-to threading, read receipts
- Real-time via Laravel Reverb (WebSockets)
- FCM push notifications for offline users
- Secure private file storage with signed temporary URLs

**Base URL:** `https://your-domain.com/api/v3`  
**All chat endpoints require:** `Authorization: Bearer {sanctum_token}`

---

## Architecture

```
app/Modules/DirectChat/
├── Controllers/
│   ├── DirectChatController.php   — All chat API endpoints
│   └── ChatFileController.php     — Signed file download
├── Services/
│   ├── DirectChatService.php      — Core business logic
│   ├── ChatFileService.php        — File upload/download/delete
│   └── ChatNotificationService.php — FCM push notifications
├── Models/
│   ├── Conversation.php
│   ├── ConversationParticipant.php
│   ├── Message.php                — SoftDeletes + MessageObserver
│   ├── MessageRead.php
│   └── MessageReaction.php
├── Events/
│   ├── MessageSent.php            — Broadcast: message.sent
│   ├── MessageDeleted.php         — Broadcast: message.deleted
│   ├── MessageRead.php            — Broadcast: message.read
│   ├── MessageReacted.php         — Broadcast: message.reacted
│   └── UserTyping.php             — Broadcast: user.typing
├── Jobs/
│   └── SendChatNotificationJob.php — Queued FCM dispatch
├── Observers/
│   └── MessageObserver.php        — Cleans up files on message delete
├── Requests/
│   ├── CreateConversationRequest.php
│   ├── SendMessageRequest.php
│   ├── ReactToMessageRequest.php
│   ├── UpdateConversationRequest.php
│   └── ManageParticipantsRequest.php
└── Resources/
    ├── ConversationListResource.php
    ├── ConversationResource.php
    └── MessageResource.php
```

---

## Database Schema

### `conversations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| type | enum | `private`, `case_group`, `social_group` |
| name | string, nullable | null for private chats |
| description | text, nullable | group description |
| image | string, nullable | group avatar path (public disk) |
| created_by | FK → users | cascade delete |
| created_at / updated_at | timestamps | |

### `conversation_participants`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| conversation_id | FK → conversations | cascade |
| user_id | FK → users | cascade |
| role | enum | `admin`, `member` |
| joined_at | timestamp | |
| last_read_at | timestamp, nullable | used for unread count |
| mute_notifications | boolean | default false; true for social_group auto-join |
| created_at / updated_at | timestamps | |
| **unique** | (conversation_id, user_id) | |

### `messages`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| conversation_id | FK → conversations | cascade |
| sender_id | FK → users | cascade |
| type | enum | `text`, `image`, `voice`, `file` |
| content | text, nullable | null for file-only messages |
| file_metadata | json, nullable | `{original_name, disk_path, mime_type, size_bytes}` |
| reply_to_id | FK → messages, nullable | set null on delete |
| deleted_at | softDeletes | "delete for everyone" |
| created_at / updated_at | timestamps | |
| **index** | (conversation_id, created_at) | |

### `message_reads`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| message_id | FK → messages | cascade |
| user_id | FK → users | cascade |
| read_at | timestamp | no timestamps |
| **unique** | (message_id, user_id) | |

### `message_reactions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| message_id | FK → messages | cascade |
| user_id | FK → users | cascade |
| reaction | string(20) | emoji character |
| created_at / updated_at | timestamps | |
| **unique** | (message_id, user_id, reaction) | one reaction type per user |

---

## Conversation Types

| Type | Who can create | Who can join | Read receipts | Notes |
|------|---------------|-------------|---------------|-------|
| `private` | Any user | N/A (2 people only) | Per-message (`message_reads`) | Auto-created on first `POST /chat/direct/{userId}` |
| `case_group` | Doctors only | Invite only (admin adds) | Per-message (`message_reads`) | All participants must be doctors |
| `social_group` | Any user | Open self-join | `last_read_at` pivot only | FCM muted by default on join |

---

## Real-Time (WebSocket)

**Technology:** Laravel Reverb (self-hosted WebSocket server)  
**Channel type:** Presence channels — `conversation.{conversationId}`

### Connecting (client side)
```javascript
// Using Laravel Echo + Pusher JS (compatible with Reverb)
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.REVERB_APP_KEY,
    wsHost: process.env.REVERB_HOST,
    wsPort: process.env.REVERB_PORT,
    wssPort: process.env.REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/v3/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${token}`
        }
    }
});

// Join a conversation channel
Echo.join(`conversation.${conversationId}`)
    .here((users) => { /* currently online users */ })
    .joining((user) => { /* user came online */ })
    .leaving((user) => { /* user went offline */ })
    .listen('.message.sent', (e) => { /* new message */ })
    .listen('.message.deleted', (e) => { /* message removed */ })
    .listen('.message.read', (e) => { /* read receipt */ })
    .listen('.message.reacted', (e) => { /* reaction added/removed */ })
    .listenForWhisper('user.typing', (e) => { /* typing indicator */ });
```

### Channel Authorization
The channel `conversation.{conversationId}` requires the user to be a participant. The auth endpoint is protected by `auth:sanctum`. Non-participants receive a 403 and cannot subscribe.

---

## File Handling

**Storage:** Private local disk at `storage/app/chat_files/` — never publicly accessible.  
**Subdirectories:** `images/`, `voice/`, `files/`  
**Filename format:** `{uuid}_{timestamp}.{ext}` (prevents guessing)

### Allowed file types per message type
| Message type | Allowed MIME types | Max size |
|---|---|---|
| `image` | image/jpeg, image/png, image/gif, image/webp | 10 MB |
| `voice` | audio/mpeg, audio/mp4, audio/ogg, audio/wav, audio/aac, audio/x-m4a | 20 MB |
| `file` | application/pdf, .doc, .docx, .xls, .xlsx, text/plain, text/csv | 20 MB |

### File download
Files are served via **signed temporary URLs** valid for **30 minutes**. The URL includes the requesting `user_id` and is validated for participant membership on download.

```
GET /api/v3/chat/files/{messageId}?signature=...&expires=...&user_id=...
```

The `file_url` field in every message response contains the pre-generated signed URL. Clients should use it directly — no authentication header needed.

### File deletion
When a message is soft-deleted (`DELETE /chat/conversations/{id}/messages/{messageId}`), the `MessageObserver` automatically deletes the associated file from disk. No orphaned files accumulate.

### Group avatars
Group conversation images use the **public disk** (`storage/app/public/group_images/`), served at `/storage/group_images/{filename}`. These are not sensitive.

---

## Push Notifications (FCM)

Push notifications are sent via Firebase Cloud Messaging to offline participants using queued jobs (`SendChatNotificationJob`, 3 retries, 30s backoff).

### FCM data payload
```json
{
  "type": "chat_message",
  "conversation_id": "42",
  "conversation_type": "private",
  "conversation_name": "",
  "msg_id": "187",
  "msg_type": "text",
  "sender_id": "15",
  "sender_name": "Ahmed Hassan"
}
```

### Notification filtering
| Scenario | FCM sent? |
|----------|-----------|
| Sender | Never |
| Recipient, `mute_notifications = false` | Yes |
| Recipient, `mute_notifications = true` | No |
| Social group (auto-joined) | No by default (muted on join) |

### Stale token cleanup
When Firebase returns `UNREGISTERED`, `NOT_FOUND`, or `INVALID_ARGUMENT` for a token, it is automatically deleted from `fcm_tokens` to prevent accumulating dead tokens.

---

## Authentication

All chat endpoints (except file download) require a Sanctum Bearer token:
```
Authorization: Bearer {token}
```

The file download endpoint uses a self-authenticating **signed URL** — no Authorization header needed.

---

## API Endpoints

### Conventions
- All responses: `{ "value": bool, "message": string, "data": object|array|null }`
- Paginated responses include Laravel's `links` and `meta` inside `data`
- Timestamps are ISO 8601: `2026-04-19T10:00:00.000000Z`
- `Content-Type: application/json` for JSON requests; `multipart/form-data` for file uploads

---

### 1. List Conversations
```
GET /api/v3/chat/conversations
```
Returns paginated list of the auth user's conversations, ordered by latest message activity. Includes last 10 messages per conversation for instant UI render (no extra request needed to open a chat).

**Query Parameters**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | No | Filter by `private`, `case_group`, or `social_group` |
| page | int | No | Pagination page (default: 1, 20 per page) |

**Response `200`**
```json
{
  "value": true,
  "message": "Conversations retrieved successfully.",
  "data": {
    "counts": {
      "all": 5,
      "private": 2,
      "case_group": 1,
      "social_group": 2
    },
    "data": [
      {
        "id": 1,
        "type": "private",
        "name": null,
        "description": null,
        "image": null,
        "my_role": "admin",
        "mute_notifications": false,
        "other_participant": {
          "id": 16,
          "name": "Sara",
          "lname": "Ahmed",
          "image": "https://...",
          "specialty": "Cardiology"
        },
        "participants": null,
        "unread_count": 3,
        "latest_message": {
          "id": 42,
          "type": "text",
          "content": "Hello!",
          "file_label": null,
          "sender_id": 16,
          "sender_name": "Sara Ahmed",
          "created_at": "2026-04-19T10:00:00.000000Z"
        },
        "messages": [
          {
            "id": 38,
            "sender": { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "..." },
            "type": "text",
            "content": "Hi there",
            "file_metadata": null,
            "file_url": null,
            "reactions": [],
            "created_at": "2026-04-19T09:55:00.000000Z"
          }
        ],
        "messages_has_more": true,
        "updated_at": "2026-04-19T10:00:00.000000Z"
      }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "per_page": 20, "total": 5 }
  }
}
```

**Notes:**
- `counts` always reflects all conversation types regardless of `?type=` filter — use for tab badges
- `other_participant` is set for `type=private`, `null` for groups
- `participants` array is set for group types, `null` for private
- `messages` = last 10, oldest-first — use as first page; scroll up fetches older via `GET /messages?before={id}`
- `messages_has_more: true` means there are messages older than the 10 returned

---

### 2. Create Conversation
```
POST /api/v3/chat/conversations
Content-Type: application/json
```
Creates a group conversation. For private chats, use `POST /chat/direct/{userId}` instead.

**Request Body**
```json
{
  "type": "case_group",
  "name": "Cardiology Case #12",
  "description": "Discussion for patient X",
  "participant_ids": [5, 8, 12]
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| type | string | Yes | `private`, `case_group`, `social_group` |
| name | string | Required unless private | max 255 |
| description | string | No | max 1000 |
| participant_ids | array | Yes | array of existing user IDs |

**Validation rules by type:**
- `private`: exactly 1 participant ID; returns existing conversation if already exists
- `case_group`: creator must have `doctor` role; all participants must have `doctor` role

**Response `201`** — `ConversationResource`
```json
{
  "value": true,
  "message": "Conversation created successfully.",
  "data": {
    "id": 7,
    "type": "case_group",
    "name": "Cardiology Case #12",
    "description": "Discussion for patient X",
    "image": null,
    "created_by": 1,
    "creator": { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "..." },
    "participants": [
      { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "...", "specialty": "...", "role": "admin", "joined_at": "...", "mute_notifications": false },
      { "id": 5, "name": "...", "lname": "...", "image": "...", "specialty": "...", "role": "member", "joined_at": "...", "mute_notifications": false }
    ],
    "created_at": "2026-04-19T10:00:00.000000Z",
    "updated_at": "2026-04-19T10:00:00.000000Z"
  }
}
```

**Response `200`** — returned instead of 201 if private conversation already exists (idempotent).

**Error Responses**
| Code | Message |
|------|---------|
| 403 | Only doctors can create case groups. |
| 422 | Case groups can only include doctors. |
| 422 | Private conversations require exactly one other participant. |

---

### 3. Get Conversation Details
```
GET /api/v3/chat/conversations/{id}
```
Returns full conversation details with all participants. Requires auth user to be a participant.

**Response `200`** — `ConversationResource` (same shape as Create response)

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You are not a member of this conversation. |
| 404 | Conversation not found. |

---

### 4. Update Conversation
```
PUT /api/v3/chat/conversations/{id}
Content-Type: multipart/form-data
```
Updates group name, description, or avatar. Admin only. Not allowed on private conversations.

**Request Body (all optional)**
| Field | Type | Rules |
|-------|------|-------|
| name | string | max 255 |
| description | string | max 1000 |
| image | file | max 5 MB, jpg/jpeg/png/webp |

**Response `200`** — `ConversationResource`

**Error Responses**
| Code | Message |
|------|---------|
| 403 | Only group admins can update conversation details. |
| 422 | Private conversations cannot be updated. |

---

### 5. Get Messages (Paginated)
```
GET /api/v3/chat/conversations/{id}/messages
GET /api/v3/chat/conversations/{id}/messages?before={messageId}
```
Returns up to 30 messages, newest-first then reversed (oldest-first in response). Automatically marks all messages as read for the auth user.

**Query Parameters**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| before | int | No | Cursor — fetch messages older than this message ID |

**Response `200`**
```json
{
  "value": true,
  "message": "Messages retrieved successfully.",
  "data": [
    {
      "id": 42,
      "conversation_id": 1,
      "sender": {
        "id": 16,
        "name": "Sara",
        "lname": "Ahmed",
        "image": "https://...",
        "specialty": "Cardiology"
      },
      "type": "text",
      "content": "Hello!",
      "file_metadata": null,
      "file_url": null,
      "reply_to": null,
      "reads": [
        { "user_id": 1, "name": "Mohamed", "lname": "Ibrahim", "read_at": "2026-04-19T10:01:00.000000Z" }
      ],
      "reads_count": 1,
      "reactions": [
        {
          "emoji": "👍",
          "count": 2,
          "users": [
            { "id": 1, "name": "Mohamed", "lname": "Ibrahim" }
          ]
        }
      ],
      "created_at": "2026-04-19T10:00:00.000000Z",
      "updated_at": "2026-04-19T10:00:00.000000Z"
    }
  ],
  "has_more": true
}
```

**Pagination flow:**
```
First open:   use the `messages` array embedded in GET /conversations response
Scroll up:    GET /conversations/{id}/messages?before={oldest_message_id_from_current_list}
Keep scrolling: repeat with the oldest ID from each response until has_more = false
```

**Notes:**
- `reads` is populated for `private` and `case_group` only (not `social_group`)
- `reply_to` contains a condensed version of the replied-to message
- Fetching messages auto-reads — no separate read endpoint

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You are not a member of this conversation. |

---

### 6. Send Message
```
POST /api/v3/chat/conversations/{id}/messages
Content-Type: multipart/form-data  (when sending files)
Content-Type: application/json     (text only)
```
Sends a message to an existing conversation. Broadcasting and FCM push happen automatically.

**Request Body**
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| type | string | Yes | `text`, `image`, `voice`, `file` |
| content | string | Required if type=text | max 5000 chars |
| file | file | Required if type≠text | See [File Handling](#file-handling) for allowed types/sizes |
| reply_to_id | int | No | Must be a valid message ID in this conversation |

**Response `201`** — `MessageResource`
```json
{
  "value": true,
  "message": "Message sent successfully.",
  "data": {
    "id": 43,
    "conversation_id": 1,
    "sender": { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "...", "specialty": "..." },
    "type": "image",
    "content": null,
    "file_metadata": {
      "original_name": "xray.jpg",
      "mime_type": "image/jpeg",
      "size_bytes": 204800
    },
    "file_url": "https://your-domain.com/api/v3/chat/files/43?signature=...&expires=1745...",
    "reply_to": null,
    "reads": [],
    "reads_count": 0,
    "reactions": [],
    "created_at": "2026-04-19T10:05:00.000000Z",
    "updated_at": "2026-04-19T10:05:00.000000Z"
  }
}
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You are not a member of this conversation. |
| 403 | Only doctors can send messages in case groups. |
| 422 | Validation error (content required, invalid file type, etc.) |

**Side effects:**
- Broadcasts `message.sent` on `conversation.{id}` channel
- Queues `SendChatNotificationJob` for unmuted participants

---

### 7. Send Direct Message (Private Chat)
```
POST /api/v3/chat/direct/{userId}
Content-Type: multipart/form-data | application/json
```
Sends a message directly to a user. If no private conversation exists between the two users, one is automatically created. If one already exists, it is reused.

**Path Parameter**
| Param | Description |
|-------|-------------|
| userId | Target user's ID |

**Request Body** — same as [Send Message](#6-send-message)

**Response `201`** — `MessageResource` (same as Send Message)

The response `data.conversation_id` contains the conversation ID. Clients should cache this to avoid calling this endpoint repeatedly — use [Send Message](#6-send-message) once the `conversation_id` is known.

**Error Responses**
| Code | Message |
|------|---------|
| 404 | Recipient not found. |
| 422 | You cannot send a message to yourself. |

---

### 8. Delete Message
```
DELETE /api/v3/chat/conversations/{id}/messages/{messageId}
```
Soft-deletes a message. Only the message sender can delete their own message. If the message had a file attached, the file is automatically removed from disk (via `MessageObserver`). All other clients receive a `message.deleted` broadcast.

**Response `200`**
```json
{
  "value": true,
  "message": "Message deleted."
}
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You can only delete your own messages. |
| 403 | You are not a member of this conversation. |
| 404 | Message not found. |

**Side effects:**
- Broadcasts `message.deleted` on `conversation.{id}` channel
- File deleted from `chat_private` disk if message had attachment

---

### 9. React to Message
```
POST /api/v3/chat/conversations/{id}/reactions
Content-Type: application/json
```
Adds or removes an emoji reaction. Toggling — if the same reaction already exists for this user on this message, it is removed.

**Request Body**
```json
{
  "message_id": 42,
  "reaction": "👍"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| message_id | int | Yes | Must exist in `messages` table |
| reaction | string | Yes | max 20 chars (emoji or short text) |

**Response `200`**
```json
{
  "value": true,
  "message": "Reaction added.",
  "data": {
    "reactions": [
      {
        "emoji": "👍",
        "count": 2,
        "users": [
          { "id": 1, "name": "Mohamed" },
          { "id": 5, "name": "Sara" }
        ]
      }
    ]
  }
}
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You are not a member of this conversation. |
| 403 | Message does not belong to this conversation. |
| 404 | Message not found. |

**Side effects:**
- Broadcasts `message.reacted` on `conversation.{id}` channel

---

### 10. Download File
```
GET /api/v3/chat/files/{messageId}?signature=...&expires=...&user_id=...
```
Streams a private chat file. The full URL is provided in the `file_url` field of every message — clients do not construct this URL manually.

**Notes:**
- No `Authorization` header needed — the URL is self-authenticating via signature
- URL expires after **30 minutes**
- Returns `403` for invalid/expired signatures
- Returns `403` if `user_id` in URL is not a participant of the conversation
- Streams the file with the original filename as `Content-Disposition: attachment`

---

### 11. Join Conversation
```
POST /api/v3/chat/conversations/{id}/join
```
Self-join a `social_group`. Not allowed for `private` or `case_group`.  
Joined with `mute_notifications = true` by default (to prevent FCM spam in large groups).

**Response `200`**
```json
{ "value": true, "message": "Joined conversation successfully." }
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | You can only join open social groups. |
| 422 | You are already a member of this conversation. |

---

### 12. Leave Conversation
```
POST /api/v3/chat/conversations/{id}/leave
```
Leave a group conversation. Not allowed for `private`.  
If the leaving user is the only admin, the earliest-joined member is automatically promoted to admin.

**Response `200`**
```json
{ "value": true, "message": "Left conversation successfully." }
```

**Error Responses**
| Code | Message |
|------|---------|
| 422 | You cannot leave a private conversation. |
| 404 | You are not a member of this conversation. |

---

### 13. Add Participants
```
POST /api/v3/chat/conversations/{id}/participants
Content-Type: application/json
```
Admin only. Adds one or more users to a group. Not allowed on `private`. For `case_group`, all added users must have the `doctor` role.

**Request Body**
```json
{ "user_ids": [5, 8, 12] }
```

**Response `200`**
```json
{ "value": true, "message": "3 participant(s) added.", "data": null }
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | Only group admins can add participants. |
| 422 | Cannot add participants to a private conversation. |
| 422 | Case groups can only include doctors. |

---

### 14. Remove Participant
```
DELETE /api/v3/chat/conversations/{id}/participants/{userId}
```
Admin only. Removes a specific participant from a group. Cannot remove yourself — use Leave instead.

**Response `200`**
```json
{ "value": true, "message": "Participant removed successfully." }
```

**Error Responses**
| Code | Message |
|------|---------|
| 403 | Only group admins can remove participants. |
| 422 | Use the leave endpoint to remove yourself. |
| 404 | User is not a member of this conversation. |

---

### 15. Mute / Unmute Notifications
```
POST /api/v3/chat/conversations/{id}/mute
Content-Type: application/json
```
Toggle push notification mute for the auth user in a specific conversation. Affects FCM — muted participants do not receive push notifications for new messages. WebSocket events are unaffected.

**Request Body**
```json
{ "mute": true }
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| mute | boolean | No | `true` to mute, `false` to unmute. Defaults to `true` if omitted. |

**Response `200`**
```json
{
  "value": true,
  "message": "Notifications muted.",
  "data": { "mute_notifications": true }
}
```

---

### 16. Typing Indicator
```
POST /api/v3/chat/conversations/{id}/typing
Content-Type: application/json
```
Broadcasts a typing event to other participants via WebSocket. Does not persist to DB.

**Request Body**
```json
{ "is_typing": true }
```

**Response `200`**
```json
{ "value": true, "message": "Typing status broadcast." }
```

---

### 17. Search Users
```
GET /api/v3/chat/users/search?q={query}
```
Searches users by name or email. Use before creating a new private or group conversation.

**Query Parameters**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| q | string | Yes | Min 2 characters |

**Response `200`**
```json
{
  "value": true,
  "message": "Users retrieved.",
  "data": [
    {
      "id": 5,
      "name": "Sara",
      "lname": "Ahmed",
      "email": "sara@example.com",
      "image": "https://...",
      "specialty": "Cardiology"
    }
  ]
}
```

---

### 18. User Profile with Chat Context
```
GET /api/v3/showAnotherProfile/{id}
```
Returns another user's profile. Includes the private conversation ID and last 10 messages if a private conversation already exists between the auth user and this profile.

**Response `200`** (chat fields shown — other profile fields omitted for brevity)
```json
{
  "value": true,
  "chat_id": 7,
  "chat_messages": [
    {
      "id": 38,
      "sender": { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "..." },
      "type": "text",
      "content": "Hi there",
      "file_metadata": null,
      "file_url": null,
      "reactions": [],
      "created_at": "2026-04-19T09:55:00.000000Z"
    }
  ],
  "chat_has_more": false
}
```

**Notes:**
- `chat_id` is `null` if no private conversation exists yet → use `POST /chat/direct/{id}` to start one
- `chat_messages` is `null` if no conversation exists
- `chat_has_more: true` → use `GET /chat/conversations/{chat_id}/messages?before={oldest_id}` for pagination

---

## WebSocket Events Reference

All events broadcast on `presence-conversation.{conversationId}` (Laravel prefixes presence channels automatically).

### `message.sent`
Fired when a new message is sent.
```json
{
  "message": {
    "id": 43,
    "conversation_id": 1,
    "sender": { "id": 1, "name": "Mohamed", "lname": "Ibrahim", "image": "...", "specialty": "..." },
    "type": "text",
    "content": "Hello!",
    "file_metadata": null,
    "file_url": null,
    "reply_to": null,
    "reads": [],
    "reads_count": 0,
    "reactions": [],
    "created_at": "2026-04-19T10:05:00.000000Z",
    "updated_at": "2026-04-19T10:05:00.000000Z"
  }
}
```

### `message.deleted`
Fired when a message is deleted by its sender.
```json
{
  "message_id": 43,
  "conversation_id": 1
}
```
Clients should remove the message from their local list when this is received.

### `message.read`
Fired when a participant reads messages.
```json
{
  "conversation_id": 1,
  "user_id": 5,
  "last_read_message_id": 43,
  "read_at": "2026-04-19T10:06:00.000000Z"
}
```

### `message.reacted`
Fired when a reaction is added or removed.
```json
{
  "message_id": 42,
  "conversation_id": 1,
  "user_id": 5,
  "user_name": "Sara",
  "reaction": "👍",
  "action": "added"
}
```
`action` is `"added"` or `"removed"`.

### `user.typing`
Fired via whisper when a user is typing.
```json
{
  "conversation_id": 1,
  "user_id": 5,
  "user_name": "Sara",
  "is_typing": true
}
```
Send `is_typing: false` when the user stops. Listen with `.listenForWhisper('user.typing', ...)`.

---

## Client Integration Flow

### Flow 1: App startup / opening chat list
```
1. GET /api/v3/chat/conversations
   → Response includes counts{} for tab badges
   → Response includes messages[] (last 10) per conversation
   → Render chat list immediately — no extra requests needed

2. Subscribe to each conversation's WebSocket channel as the user scrolls/opens
3. Show unread_count badges from the response
```

### Flow 2: Opening a conversation
```
1. The messages[] from the chat list response is the first page
   → Render immediately without any API call

2. If messages_has_more = true, show "load older" / detect scroll-to-top
   → GET /conversations/{id}/messages?before={oldest_message_id}
   → Repeat until has_more = false

3. Subscribe to conversation.{id} WebSocket channel
   → Listen for message.sent, message.deleted, message.read, message.reacted
   → Whisper user.typing on keypress, stop on blur/send

4. Fetching messages auto-marks them as read (no separate /read endpoint)
```

### Flow 3: Sending a text message
```
1. POST /chat/conversations/{id}/messages
   { "type": "text", "content": "Hello!" }

2. API returns the created MessageResource
3. WebSocket broadcasts message.sent to all other participants
4. FCM push sent to offline/unmuted participants via queue
```

### Flow 4: Sending a file
```
1. POST /chat/conversations/{id}/messages
   multipart/form-data: type=image, file={binary}

2. File stored on private disk (never publicly accessible)
3. Response includes file_url (signed, 30-min expiry)
4. Clients use file_url directly to display/download
5. On next load, file_url is regenerated fresh (always valid)
```

### Flow 5: Starting a new private chat
```
1. GET /chat/users/search?q=sara → pick a user

2. POST /chat/direct/{userId}
   { "type": "text", "content": "Hi!" }
   → Creates conversation if not exists, sends message
   → response.data.conversation_id — cache this!

3. All subsequent messages: POST /chat/conversations/{id}/messages
```

### Flow 6: Viewing another user's profile with chat history
```
1. GET /showAnotherProfile/{userId}
   → Returns chat_id (null if no chat yet)
   → Returns chat_messages (last 10, null if no chat)
   → Returns chat_has_more

2a. If chat_id is null → show "Start Chat" button
    → On tap: POST /chat/direct/{userId} with first message

2b. If chat_id is set → render chat_messages immediately
    → If chat_has_more → paginate via GET /chat/conversations/{chat_id}/messages?before={id}
```

### Flow 7: Group management
```
Create group:
  POST /chat/conversations { type, name, description, participant_ids }

Add members (admin only):
  POST /chat/conversations/{id}/participants { user_ids: [...] }

Remove member (admin only):
  DELETE /chat/conversations/{id}/participants/{userId}

Update name/description/avatar (admin only):
  PUT /chat/conversations/{id}  (multipart if uploading image)

Leave group:
  POST /chat/conversations/{id}/leave
  → If last admin, earliest member auto-promoted

Join social group (open):
  POST /chat/conversations/{id}/join
  → Joined muted by default; unmute with POST /mute { mute: false }
```

---

## Key Design Decisions

| Decision | Reason |
|----------|--------|
| **Presence channels** | Provides online/offline tracking and typing indicators on the same channel |
| **Cursor pagination** (`?before=msgId`) | Avoids shifting page offsets when new messages arrive during scroll |
| **Signed routes for file downloads** | Local disk doesn't support `temporaryUrl()`; signed routes give equivalent 30-min expiry |
| **`last_read_at` pivot** | Enables unread count in a single JOIN query — no N+1 |
| **`message_reads` only for private/case_group** | Social groups at scale would have billions of rows; `last_read_at` pivot is sufficient |
| **FCM muted by default on social group join** | Prevents notification bombs in large groups |
| **`SoftDeletes` on messages** | "Delete for everyone" without breaking FK integrity; observer cleans files |
| **Auto-read on fetch** | No extra `/read` endpoint — simplifies client logic |
| **Private chat lazy creation** | `POST /direct/{userId}` finds or creates atomically — no pre-creation step needed |
| **`file_metadata` cast as `json`** | `array` cast throws on null; `json` returns null safely |
| **Batch queries for unread/has_more** | Pre-computed in service via 2 batch queries; resource reads attributes — no N+1 |
| **Group avatars on public disk** | Not sensitive; `chat_files` private disk is for message attachments only |

---

## TODO (Pre-Production)
- [ ] Add `throttle:60,1` middleware to `POST /chat/conversations/{id}/messages`
- [ ] Add `throttle:60,1` middleware to `POST /chat/direct/{userId}`
- [ ] Feature tests: `tests/Feature/DirectChat/ConversationTest.php`
- [ ] Feature tests: `tests/Feature/DirectChat/MessageTest.php`
