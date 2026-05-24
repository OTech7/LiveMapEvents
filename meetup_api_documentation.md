# Meetup App — Features, Functions & API Documentation

---

## Table of Contents

1. [App Overview](#overview)
2. [Core Features & Functions](#features)
3. [API Documentation](#api-documentation)
    - [Authentication](#authentication)
    - [Groups](#groups)
    - [Events](#events)
    - [Members](#members)
    - [RSVPs](#rsvps)
    - [Comments](#comments)
    - [Photos](#photos)
    - [Categories](#categories)
    - [Search & Discovery](#search--discovery)
    - [Notifications](#notifications)
    - [Payments & Dues](#payments--dues)
4. [Error Codes](#error-codes)
5. [Rate Limiting](#rate-limiting)
6. [Webhooks](#webhooks)

---

## Overview

Meetup is a social platform that connects people around shared interests. It enables organizers to create local or
virtual groups around topics like technology, fitness, arts, business, and more — and allows members to discover, RSVP,
and attend events in their area or online.

**Base URL:** `https://api.meetup.com/v3`

**Data Format:** All requests and responses use `application/json`

**Versioning:** Current stable version is `v3`

---

## Core Features & Functions

### 1. Groups

- **Create & Manage Groups** — Organizers can create groups around any topic or interest, set group rules, configure
  privacy (public/private), and manage membership.
- **Group Topics & Categories** — Groups are tagged with topics (e.g., "Python", "Hiking", "Yoga") and placed under
  broader categories.
- **Group Roles** — Three roles exist: `organizer`, `co-organizer`, and `member`. Organizers have full admin access.
  Co-organizers can manage events and members.
- **Membership Approval** — Groups can be open (anyone can join), closed (requires organizer approval), or
  invitation-only.
- **Group Dues** — Organizers can charge recurring membership dues via Stripe integration.
- **Group Photo & Branding** — Groups can have cover photos, logos, and custom bios.
- **Group Announcements** — Organizers can broadcast announcements to all members.
- **Group Discussions** — Members can post discussions/threads inside a group (message board).

---

### 2. Events

- **Create Events** — Organizers create events with title, description, date/time, location (in-person or online), max
  attendees, and RSVP deadline.
- **Recurring Events** — Support for weekly, biweekly, or monthly recurring event series.
- **Event Visibility** — Events can be public (visible to all), members-only, or unlisted.
- **Venue Management** — Attach a physical venue with address, map link, and venue notes. Alternatively, set as online
  with a Zoom/Meet/custom link.
- **Waitlists** — When max attendees is reached, additional RSVPs go to a waitlist and auto-promote when spots open.
- **Event Fees** — Organizers can charge a fee per attendee, processed via Stripe.
- **Event Photos** — Members can upload photos to an event's photo album.
- **Event Comments** — Members can comment on events before and after.
- **Event Check-in** — Organizers can mark attendees as checked in via QR code or manual check-in.
- **Event Reminders** — Automated reminder emails/push notifications sent 24 hours and 1 hour before an event.
- **Event Cancellation** — Organizers can cancel events; all RSVPed members are automatically notified.
- **Draft Events** — Events can be saved as drafts before publishing.

---

### 3. Members & Profiles

- **Member Profiles** — Each member has a profile with bio, photo, location, topics of interest, and membership history.
- **Social Links** — Members can link Twitter, LinkedIn, and other social accounts.
- **Member Badges** — Earned for milestones (e.g., "First Event", "10 Events Attended").
- **Friends & Connections** — Members can follow other members and see their upcoming events.
- **Member Privacy** — Control visibility of profile, attendance history, and group memberships.
- **Account Settings** — Email preferences, notification settings, payment methods, and connected accounts.

---

### 4. Discovery & Search

- **Location-based Discovery** — Find groups and events near a city, zip code, or by GPS coordinates with a configurable
  radius.
- **Topic & Category Browsing** — Browse groups by interest category (Tech, Outdoors, Arts, etc.).
- **Keyword Search** — Full-text search across groups, events, and members.
- **Recommended Groups** — Personalized group recommendations based on member interests and location.
- **Upcoming Events Feed** — A personalized feed of upcoming events from joined groups and recommended groups.
- **Online Events Filter** — Toggle to show only virtual events accessible from anywhere.

---

### 5. Notifications

- **Push Notifications** — Mobile push for new events, event reminders, RSVP updates, and messages.
- **Email Notifications** — Configurable digests (daily/weekly) and transactional emails.
- **In-App Notifications** — Notification center inside the app for all activity.

---

### 6. Messaging

- **Group Chat** — Real-time group message threads for organizers and members.
- **Direct Messages** — 1:1 messaging between members.
- **Organizer Messaging** — Blast messages to all RSVPed attendees for a specific event.

---

### 7. Payments

- **Stripe Integration** — All payments processed via Stripe Connect.
- **Group Dues** — Recurring membership fees set by organizer.
- **Event Fees** — Per-event ticketing with optional refund policies.
- **Payout Management** — Organizers connect their Stripe account for automatic payouts.
- **Refunds** — Organizers can issue full or partial refunds through the dashboard.

---

### 8. Analytics (Organizer Dashboard)

- **Event Attendance Trends** — Charts showing RSVP counts and attendance over time.
- **Member Growth** — Graph of new members joining the group over weeks/months.
- **Engagement Metrics** — Comments, photo uploads, and discussion activity.
- **Revenue Reports** — Dues and event fee revenue summaries.
- **RSVP Export** — Export attendee list to CSV.

---

---

## API Documentation

### Base URL

```
https://api.meetup.com/v3
```

---

## Authentication

Meetup uses **OAuth 2.0** for all API access.

### OAuth 2.0 Flow

#### Step 1 — Authorization Request

Redirect the user to:

```
GET https://secure.meetup.com/oauth2/authorize
  ?client_id={CLIENT_ID}
  &response_type=code
  &redirect_uri={REDIRECT_URI}
  &scope=basic+event_management+group_edit+rsvp+reporting
  &state={RANDOM_STATE_STRING}
```

#### Step 2 — Exchange Code for Token

```
POST https://secure.meetup.com/oauth2/access
Content-Type: application/x-www-form-urlencoded

client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
&grant_type=authorization_code
&redirect_uri={REDIRECT_URI}
&code={AUTHORIZATION_CODE}
```

**Response:**

```json
{
  "access_token": "eyJhbGciOiJSUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "refresh_token": "def502003d9a...",
  "scope": "basic event_management group_edit rsvp reporting"
}
```

#### Step 3 — Refresh Token

```
POST https://secure.meetup.com/oauth2/access
Content-Type: application/x-www-form-urlencoded

client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
&grant_type=refresh_token
&refresh_token={REFRESH_TOKEN}
```

#### Using the Token

Include the access token in every API request:

```
Authorization: Bearer {ACCESS_TOKEN}
```

#### Available Scopes

| Scope              | Description                          |
|--------------------|--------------------------------------|
| `basic`            | Read member profile & group info     |
| `event_management` | Create, edit, delete events          |
| `group_edit`       | Manage group settings and members    |
| `rsvp`             | RSVP to events on behalf of member   |
| `reporting`        | Access analytics and attendance data |
| `messaging`        | Send and read messages               |
| `profile_edit`     | Edit member profile                  |

---

## Groups

### Get Group by URL Name

```
GET /groups/{urlname}
```

**Path Parameters:**

| Parameter | Type   | Required | Description          |
|-----------|--------|----------|----------------------|
| urlname   | string | Yes      | The group's URL slug |

**Example Request:**

```
GET /groups/nyc-python-developers
Authorization: Bearer {ACCESS_TOKEN}
```

**Example Response:**

```json
{
  "id": 1234567,
  "name": "NYC Python Developers",
  "urlname": "nyc-python-developers",
  "description": "A group for Python enthusiasts in New York City.",
  "created": 1609459200000,
  "city": "New York",
  "state": "NY",
  "country": "US",
  "lat": 40.7128,
  "lon": -74.0060,
  "timezone": "America/New_York",
  "join_mode": "open",
  "visibility": "public",
  "status": "active",
  "members": 4520,
  "organizer": {
    "id": 98765,
    "name": "Jane Smith",
    "photo": {
      "photo_link": "https://secure.meetupstatic.com/photos/member/1/2/3/member_photo.jpeg"
    }
  },
  "group_photo": {
    "photo_link": "https://secure.meetupstatic.com/photos/event/1/2/3/group_photo.jpeg"
  },
  "topics": [
    {
      "id": 10,
      "name": "Python",
      "urlkey": "python"
    },
    {
      "id": 14,
      "name": "Data Science",
      "urlkey": "data-science"
    }
  ],
  "category": {
    "id": 34,
    "name": "Tech",
    "shortname": "tech"
  },
  "link": "https://www.meetup.com/nyc-python-developers/",
  "next_event": {
    "id": "evt_abc123",
    "name": "Monthly Python Meetup",
    "yes_rsvp_count": 87,
    "time": 1716220800000,
    "utc_offset": -18000000
  }
}
```

---

### List Groups Near Location

```
GET /groups
```

**Query Parameters:**

| Parameter | Type    | Required | Description                                    |
|-----------|---------|----------|------------------------------------------------|
| lat       | float   | No       | Latitude of search center                      |
| lon       | float   | No       | Longitude of search center                     |
| city      | string  | No       | City name (alternative to lat/lon)             |
| country   | string  | No       | ISO 3166-1 alpha-2 country code                |
| radius    | float   | No       | Search radius in miles (default: 25, max: 100) |
| topic_id  | integer | No       | Filter by topic ID                             |
| category  | integer | No       | Filter by category ID                          |
| page      | integer | No       | Number of results (default: 20, max: 200)      |
| offset    | integer | No       | Pagination offset                              |
| order     | string  | No       | `id`, `name`, `newest`, `members` (default)    |

**Example Request:**

```
GET /groups?lat=40.7128&lon=-74.0060&radius=10&category=34&page=5
Authorization: Bearer {ACCESS_TOKEN}
```

**Example Response:**

```json
{
  "results": [
    {
      "id": 1234567,
      "name": "NYC Python Developers",
      "urlname": "nyc-python-developers",
      "members": 4520,
      "city": "New York",
      "distance": 1.3
    }
  ],
  "meta": {
    "count": 5,
    "total_count": 42,
    "page": 5,
    "offset": 0,
    "next": "https://api.meetup.com/v3/groups?offset=5"
  }
}
```

---

### Create a Group

```
POST /groups
```

**Request Body:**

| Field       | Type    | Required | Description                                |
|-------------|---------|----------|--------------------------------------------|
| name        | string  | Yes      | Group name (max 60 chars)                  |
| urlname     | string  | Yes      | Unique URL slug (lowercase, hyphens only)  |
| description | string  | Yes      | Group description (HTML allowed, max 3000) |
| city        | string  | Yes      | City name                                  |
| country     | string  | Yes      | ISO country code                           |
| state       | string  | No       | State/province (required for US/CA)        |
| zip         | string  | No       | Postal code                                |
| lat         | float   | No       | Latitude                                   |
| lon         | float   | No       | Longitude                                  |
| join_mode   | string  | No       | `open`, `closed`, or `approval`            |
| visibility  | string  | No       | `public` or `private`                      |
| topics      | array   | No       | Array of topic IDs                         |
| category_id | integer | No       | Category ID                                |

**Example Request:**

```json
POST /groups
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
"name": "Austin React Developers",
"urlname": "austin-react-devs",
"description": "<p>For React developers in Austin, TX.</p>",
"city": "Austin",
"state": "TX",
"country": "US",
"zip": "78701",
"join_mode": "open",
"visibility": "public",
"topics": [45, 67, 89
],
"category_id": 34
}
```

**Response:** `201 Created` with the full group object.

---

### Update a Group

```
PATCH /groups/{urlname}
```

Accepts the same fields as `POST /groups`. Only include fields you want to change.

---

### Delete a Group

```
DELETE /groups/{urlname}
```

**Response:** `204 No Content`

---

### Get Group Members

```
GET /groups/{urlname}/members
```

**Query Parameters:**

| Parameter | Type    | Required | Description                              |
|-----------|---------|----------|------------------------------------------|
| role      | string  | No       | `organizer`, `coorganizer`, `member`     |
| page      | integer | No       | Results per page (default: 20, max: 200) |
| offset    | integer | No       | Pagination offset                        |

**Example Response:**

```json
{
  "results": [
    {
      "id": 11223344,
      "name": "Alice Johnson",
      "role": "member",
      "joined": 1620000000000,
      "city": "New York",
      "country": "US",
      "photo": {
        "photo_link": "https://secure.meetupstatic.com/photos/member/alice.jpeg"
      },
      "topics": [
        {
          "id": 10,
          "name": "Python"
        }
      ]
    }
  ],
  "meta": {
    "count": 20,
    "total_count": 4520
  }
}
```

---

## Events

### Get Event by ID

```
GET /events/{eventId}
```

**Example Response:**

```json
{
  "id": "evt_abc123",
  "name": "Monthly Python Meetup — May 2026",
  "status": "upcoming",
  "created": 1715000000000,
  "updated": 1715100000000,
  "time": 1716220800000,
  "duration": 7200000,
  "utc_offset": -18000000,
  "waitlist_count": 0,
  "yes_rsvp_count": 87,
  "rsvp_limit": 100,
  "rsvp_close_offset": "-1d",
  "description": "<p>Join us for our monthly Python meetup!</p>",
  "event_url": "https://www.meetup.com/nyc-python-developers/events/evt_abc123/",
  "visibility": "public",
  "is_online_event": false,
  "venue": {
    "id": 55566,
    "name": "WeWork Bryant Park",
    "address_1": "25 W 39th St",
    "city": "New York",
    "state": "NY",
    "zip": "10018",
    "country": "US",
    "lat": 40.7538,
    "lon": -73.9840
  },
  "group": {
    "id": 1234567,
    "name": "NYC Python Developers",
    "urlname": "nyc-python-developers"
  },
  "fee": {
    "amount": 5.00,
    "currency": "USD",
    "description": "To cover venue cost",
    "required": true,
    "accepts": "paypal,cash"
  },
  "hosts": [
    {
      "id": 98765,
      "name": "Jane Smith"
    }
  ],
  "photo_album": {
    "photo_count": 12,
    "album_link": "https://www.meetup.com/nyc-python-developers/photos/album_id/"
  }
}
```

---

### List Events for a Group

```
GET /groups/{urlname}/events
```

**Query Parameters:**

| Parameter       | Type    | Required | Description                                                    |
|-----------------|---------|----------|----------------------------------------------------------------|
| status          | string  | No       | `upcoming`, `past`, `draft`, `cancelled` (default: `upcoming`) |
| page            | integer | No       | Results per page (default: 20, max: 200)                       |
| offset          | integer | No       | Pagination offset                                              |
| no_earlier_than | string  | No       | ISO 8601 datetime — exclude events before this date            |
| no_later_than   | string  | No       | ISO 8601 datetime — exclude events after this date             |
| desc            | boolean | No       | Sort descending if `true`                                      |

**Example Request:**

```
GET /groups/nyc-python-developers/events?status=upcoming&page=10
Authorization: Bearer {ACCESS_TOKEN}
```

---

### Create an Event

```
POST /groups/{urlname}/events
```

**Request Body:**

| Field             | Type    | Required | Description                                            |
|-------------------|---------|----------|--------------------------------------------------------|
| name              | string  | Yes      | Event title (max 80 chars)                             |
| description       | string  | Yes      | Event description (HTML allowed, max 4000 chars)       |
| time              | integer | Yes      | Unix timestamp in milliseconds (UTC)                   |
| duration          | integer | No       | Duration in milliseconds (default: 10800000 = 3hrs)    |
| venue_id          | integer | No       | Venue ID (leave empty for online events)               |
| is_online_event   | boolean | No       | `true` for virtual events                              |
| online_event_url  | string  | No       | Meeting link (Zoom, Google Meet, etc.)                 |
| rsvp_limit        | integer | No       | Max attendees (0 = unlimited)                          |
| rsvp_close_offset | string  | No       | Time before event RSVPs close (e.g., `"-1d"`, `"-2h"`) |
| publish_status    | string  | No       | `published` or `draft` (default: `published`)          |
| guest_limit       | integer | No       | Max guests each attendee can bring (default: 0)        |
| fee               | object  | No       | Fee configuration object (see below)                   |
| hosts             | array   | No       | Array of member IDs to set as event hosts              |

**Fee Object:**

```json
{
  "amount": 10.00,
  "currency": "USD",
  "description": "Covers food and drinks",
  "required": true,
  "refund_policy": "no_refunds"
}
```

**Example Request:**

```json
POST /groups/nyc-python-developers/events
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
"name": "Python Workshop: Building REST APIs with FastAPI",
"description": "<p>Hands-on workshop covering FastAPI fundamentals.</p>",
"time": 1718640000000,
"duration": 10800000,
"venue_id": 55566,
"rsvp_limit": 50,
"rsvp_close_offset": "-1d",
"publish_status": "published",
"hosts": [98765, 11223]
}
```

**Response:** `201 Created` with full event object.

---

### Update an Event

```
PATCH /events/{eventId}
```

Accepts the same fields as `POST /groups/{urlname}/events`. Partial updates supported.

---

### Cancel an Event

```
POST /events/{eventId}/cancel
```

**Request Body:**

| Field  | Type   | Required | Description                            |
|--------|--------|----------|----------------------------------------|
| reason | string | No       | Cancellation message sent to attendees |

**Response:** `200 OK` with updated event object (status: `cancelled`).

---

### Delete an Event

```
DELETE /events/{eventId}
```

**Response:** `204 No Content`

---

### Get Upcoming Events Near Location

```
GET /events
```

**Query Parameters:**

| Parameter  | Type    | Required | Description                              |
|------------|---------|----------|------------------------------------------|
| lat        | float   | No       | Latitude                                 |
| lon        | float   | No       | Longitude                                |
| radius     | float   | No       | Search radius in miles (default: 25)     |
| topic_id   | integer | No       | Filter by topic                          |
| category   | integer | No       | Filter by category                       |
| is_online  | boolean | No       | Show only online events                  |
| start_date | string  | No       | ISO 8601 start date                      |
| end_date   | string  | No       | ISO 8601 end date                        |
| page       | integer | No       | Results per page (default: 20, max: 200) |
| offset     | integer | No       | Pagination offset                        |

---

## Members

### Get Current Authenticated Member

```
GET /members/self
```

**Example Response:**

```json
{
  "id": 11223344,
  "name": "Omar Allouni",
  "email": "omarallouni@gmail.com",
  "status": "active",
  "joined": 1600000000000,
  "city": "New York",
  "state": "NY",
  "country": "US",
  "lat": 40.7128,
  "lon": -74.0060,
  "photo": {
    "id": 9988776,
    "highres_link": "https://secure.meetupstatic.com/photos/member/omar_highres.jpeg",
    "photo_link": "https://secure.meetupstatic.com/photos/member/omar.jpeg",
    "thumb_link": "https://secure.meetupstatic.com/photos/member/omar_thumb.jpeg"
  },
  "bio": "Software developer passionate about open source.",
  "topics": [
    {
      "id": 10,
      "name": "Python"
    },
    {
      "id": 45,
      "name": "React"
    }
  ],
  "privacy": {
    "bio": "public",
    "groups": "public",
    "facebook": "hidden"
  }
}
```

---

### Get Member by ID

```
GET /members/{memberId}
```

Returns public profile data only. Private fields are hidden based on member's privacy settings.

---

### Update Current Member Profile

```
PATCH /members/self
```

**Request Body (all optional):**

| Field    | Type    | Description                     |
|----------|---------|---------------------------------|
| name     | string  | Display name                    |
| bio      | string  | Profile bio (max 250 chars)     |
| city     | string  | City name                       |
| country  | string  | ISO country code                |
| state    | string  | State/province                  |
| zip      | string  | Postal code                     |
| topics   | array   | Array of topic IDs              |
| photo_id | integer | ID of an already-uploaded photo |

---

### Get Groups the Current Member Belongs To

```
GET /members/self/groups
```

**Query Parameters:**

| Parameter | Type    | Required | Description                    |
|-----------|---------|----------|--------------------------------|
| page      | integer | No       | Results per page (default: 20) |
| offset    | integer | No       | Pagination offset              |
| role      | string  | No       | `member`, `organizer`          |

---

## RSVPs

### Get RSVPs for an Event

```
GET /events/{eventId}/rsvps
```

**Query Parameters:**

| Parameter | Type    | Required | Description                              |
|-----------|---------|----------|------------------------------------------|
| response  | string  | No       | `yes`, `no`, `waitlist`                  |
| page      | integer | No       | Results per page (default: 20, max: 200) |
| offset    | integer | No       | Pagination offset                        |

**Example Response:**

```json
{
  "results": [
    {
      "id": "rsvp_001",
      "member": {
        "id": 11223344,
        "name": "Alice Johnson",
        "photo": {
          "thumb_link": "https://secure.meetupstatic.com/photos/member/alice_thumb.jpeg"
        }
      },
      "response": "yes",
      "guests": 1,
      "updated": 1715200000000,
      "created": 1715100000000
    }
  ],
  "meta": {
    "count": 20,
    "total_count": 87
  }
}
```

---

### Create or Update an RSVP

```
POST /events/{eventId}/rsvps
```

**Request Body:**

| Field      | Type    | Required | Description                              |
|------------|---------|----------|------------------------------------------|
| response   | string  | Yes      | `yes` or `no`                            |
| guests     | integer | No       | Number of guests (default: 0)            |
| opt_to_pay | boolean | No       | Whether to opt into paying the event fee |

**Example Request:**

```json
POST /events/evt_abc123/rsvps
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
"response": "yes",
"guests": 1
}
```

**Example Response:**

```json
{
  "id": "rsvp_001",
  "event": {
    "id": "evt_abc123",
    "name": "Monthly Python Meetup"
  },
  "member": {
    "id": 11223344,
    "name": "Omar Allouni"
  },
  "response": "yes",
  "guests": 1,
  "waitlisted": false,
  "created": 1715100000000,
  "updated": 1715100000000
}
```

---

### Delete an RSVP (Cancel Attendance)

```
DELETE /events/{eventId}/rsvps/{memberId}
```

**Response:** `204 No Content`

---

### Check In a Member at an Event

```
POST /events/{eventId}/checkins
```

**Request Body:**

| Field     | Type    | Required | Description           |
|-----------|---------|----------|-----------------------|
| member_id | integer | Yes      | Member ID to check in |

**Response:** `200 OK` with check-in confirmation.

---

## Comments

### Get Comments for an Event

```
GET /events/{eventId}/comments
```

**Query Parameters:**

| Parameter | Type    | Required | Description                    |
|-----------|---------|----------|--------------------------------|
| page      | integer | No       | Results per page (default: 20) |
| offset    | integer | No       | Pagination offset              |

**Example Response:**

```json
{
  "results": [
    {
      "id": "cmt_001",
      "comment": "Looking forward to this event!",
      "created": 1715150000000,
      "updated": 1715150000000,
      "like_count": 5,
      "member": {
        "id": 11223344,
        "name": "Alice Johnson",
        "photo": {
          "thumb_link": "https://secure.meetupstatic.com/photos/member/alice_thumb.jpeg"
        }
      },
      "replies": []
    }
  ],
  "meta": {
    "count": 10,
    "total_count": 10
  }
}
```

---

### Post a Comment on an Event

```
POST /events/{eventId}/comments
```

**Request Body:**

| Field       | Type    | Required | Description                   |
|-------------|---------|----------|-------------------------------|
| comment     | string  | Yes      | Comment text (max 2000 chars) |
| in_reply_to | integer | No       | Comment ID this is a reply to |

**Example Request:**

```json
{
  "comment": "Will there be parking available?"
}
```

**Response:** `201 Created` with full comment object.

---

### Delete a Comment

```
DELETE /comments/{commentId}
```

**Response:** `204 No Content`

---

### Like a Comment

```
POST /comments/{commentId}/likes
```

**Response:** `200 OK`

---

## Photos

### Get Photos for an Event

```
GET /events/{eventId}/photos
```

**Example Response:**

```json
{
  "results": [
    {
      "id": 778899,
      "photo_link": "https://secure.meetupstatic.com/photos/event/photo1.jpeg",
      "highres_link": "https://secure.meetupstatic.com/photos/event/photo1_highres.jpeg",
      "thumb_link": "https://secure.meetupstatic.com/photos/event/photo1_thumb.jpeg",
      "created": 1716300000000,
      "member": {
        "id": 11223344,
        "name": "Alice Johnson"
      },
      "caption": "Great workshop!"
    }
  ],
  "meta": {
    "count": 12
  }
}
```

---

### Upload a Photo to an Event

```
POST /events/{eventId}/photos
Content-Type: multipart/form-data
```

**Form Fields:**

| Field   | Type   | Required | Description                      |
|---------|--------|----------|----------------------------------|
| photo   | file   | Yes      | Image file (JPEG/PNG, max 10 MB) |
| caption | string | No       | Photo caption (max 200 chars)    |

**Response:** `201 Created` with photo object.

---

### Delete a Photo

```
DELETE /photos/{photoId}
```

**Response:** `204 No Content`

---

## Categories

### List All Categories

```
GET /categories
```

**Example Response:**

```json
{
  "results": [
    {
      "id": 1,
      "name": "Arts & Culture",
      "shortname": "arts"
    },
    {
      "id": 2,
      "name": "Career & Business",
      "shortname": "career"
    },
    {
      "id": 4,
      "name": "Community & Environment",
      "shortname": "community"
    },
    {
      "id": 5,
      "name": "Dancing",
      "shortname": "dancing"
    },
    {
      "id": 6,
      "name": "Education & Learning",
      "shortname": "education"
    },
    {
      "id": 8,
      "name": "Fitness",
      "shortname": "fitness"
    },
    {
      "id": 10,
      "name": "Food & Drink",
      "shortname": "food"
    },
    {
      "id": 11,
      "name": "Games",
      "shortname": "games"
    },
    {
      "id": 13,
      "name": "Health & Wellbeing",
      "shortname": "health"
    },
    {
      "id": 14,
      "name": "Hobbies & Crafts",
      "shortname": "hobbies"
    },
    {
      "id": 15,
      "name": "Language & Ethnic Identity",
      "shortname": "language"
    },
    {
      "id": 16,
      "name": "LGBTQ+",
      "shortname": "lgbtq"
    },
    {
      "id": 20,
      "name": "Movies & Film",
      "shortname": "movies"
    },
    {
      "id": 21,
      "name": "Music",
      "shortname": "music"
    },
    {
      "id": 23,
      "name": "Outdoors & Adventure",
      "shortname": "outdoors"
    },
    {
      "id": 26,
      "name": "Parents & Family",
      "shortname": "parents"
    },
    {
      "id": 27,
      "name": "Pets & Animals",
      "shortname": "pets"
    },
    {
      "id": 28,
      "name": "Photography",
      "shortname": "photography"
    },
    {
      "id": 29,
      "name": "Religion & Spirituality",
      "shortname": "religion"
    },
    {
      "id": 31,
      "name": "Sci-Fi & Fantasy",
      "shortname": "scifi"
    },
    {
      "id": 32,
      "name": "Social",
      "shortname": "social"
    },
    {
      "id": 33,
      "name": "Sports & Recreation",
      "shortname": "sports"
    },
    {
      "id": 34,
      "name": "Technology",
      "shortname": "tech"
    },
    {
      "id": 35,
      "name": "Writing",
      "shortname": "writing"
    }
  ]
}
```

---

### List Topics

```
GET /topics
```

**Query Parameters:**

| Parameter | Type    | Required | Description                              |
|-----------|---------|----------|------------------------------------------|
| search    | string  | No       | Keyword to search topics by name         |
| category  | integer | No       | Filter topics by category                |
| page      | integer | No       | Results per page (default: 50, max: 200) |

---

## Search & Discovery

### Search Groups and Events

```
GET /search
```

**Query Parameters:**

| Parameter | Type    | Required | Description                                   |
|-----------|---------|----------|-----------------------------------------------|
| q         | string  | Yes      | Search keyword                                |
| type      | string  | No       | `groups`, `events`, or `all` (default: `all`) |
| lat       | float   | No       | Latitude for location-based results           |
| lon       | float   | No       | Longitude for location-based results          |
| radius    | float   | No       | Radius in miles (default: 25)                 |
| category  | integer | No       | Filter by category ID                         |
| is_online | boolean | No       | Include/exclude online-only results           |
| page      | integer | No       | Results per page (default: 20)                |
| offset    | integer | No       | Pagination offset                             |

**Example Request:**

```
GET /search?q=python&type=events&lat=40.7128&lon=-74.0060&radius=15
Authorization: Bearer {ACCESS_TOKEN}
```

**Example Response:**

```json
{
  "groups": [],
  "events": [
    {
      "id": "evt_abc123",
      "name": "Python Workshop: FastAPI",
      "time": 1718640000000,
      "yes_rsvp_count": 45,
      "group": {
        "name": "NYC Python Developers",
        "urlname": "nyc-python-developers"
      },
      "venue": {
        "city": "New York",
        "lat": 40.7538,
        "lon": -73.9840
      },
      "distance": 2.1
    }
  ],
  "meta": {
    "count": 1,
    "total_count": 1
  }
}
```

---

## Notifications

### Get Notifications for Current Member

```
GET /members/self/notifications
```

**Query Parameters:**

| Parameter | Type    | Required | Description                    |
|-----------|---------|----------|--------------------------------|
| page      | integer | No       | Results per page (default: 20) |
| offset    | integer | No       | Pagination offset              |

**Example Response:**

```json
{
  "results": [
    {
      "id": "notif_001",
      "type": "new_event",
      "read": false,
      "created": 1715200000000,
      "message": "NYC Python Developers posted a new event: Python Workshop",
      "link": "https://www.meetup.com/nyc-python-developers/events/evt_abc123/",
      "entity": {
        "type": "event",
        "id": "evt_abc123"
      }
    }
  ],
  "meta": {
    "unread_count": 3,
    "count": 20
  }
}
```

---

### Mark Notification as Read

```
PATCH /notifications/{notificationId}
```

**Request Body:**

```json
{
  "read": true
}
```

---

### Mark All Notifications as Read

```
POST /members/self/notifications/read_all
```

**Response:** `200 OK`

---

## Payments & Dues

### Get Group Dues Settings

```
GET /groups/{urlname}/dues
```

**Example Response:**

```json
{
  "active": true,
  "amount": 12.00,
  "currency": "USD",
  "billing_cycle": "monthly",
  "grace_period_days": 7,
  "description": "Monthly membership dues",
  "stripe_account_connected": true
}
```

---

### Update Group Dues Settings

```
PATCH /groups/{urlname}/dues
```

**Request Body:**

| Field             | Type    | Required | Description                               |
|-------------------|---------|----------|-------------------------------------------|
| active            | boolean | No       | Enable or disable dues                    |
| amount            | float   | No       | Dues amount                               |
| currency          | string  | No       | ISO 4217 currency code (e.g., `USD`)      |
| billing_cycle     | string  | No       | `monthly`, `quarterly`, `yearly`          |
| grace_period_days | integer | No       | Days after due date before access revoked |
| description       | string  | No       | Description shown to members              |

---

### Get Payment History for Current Member

```
GET /members/self/payments
```

**Example Response:**

```json
{
  "results": [
    {
      "id": "pay_001",
      "amount": 10.00,
      "currency": "USD",
      "status": "paid",
      "type": "event_fee",
      "created": 1715100000000,
      "event": {
        "id": "evt_abc123",
        "name": "Python Workshop"
      },
      "group": {
        "id": 1234567,
        "name": "NYC Python Developers"
      }
    }
  ]
}
```

---

### Issue a Refund

```
POST /payments/{paymentId}/refund
```

**Request Body:**

| Field  | Type  | Required | Description                                    |
|--------|-------|----------|------------------------------------------------|
| amount | float | No       | Partial refund amount. Full refund if omitted. |

**Response:** `200 OK` with updated payment object.

---

---

## Error Codes

All errors return a JSON body with `code`, `message`, and optional `errors` array.

```json
{
  "code": "invalid_request",
  "message": "The field 'name' is required.",
  "errors": [
    {
      "field": "name",
      "message": "This field is required."
    }
  ]
}
```

| HTTP Status | Code                  | Description                                       |
|-------------|-----------------------|---------------------------------------------------|
| 400         | `invalid_request`     | Missing or malformed request parameters           |
| 401         | `unauthorized`        | Missing or invalid access token                   |
| 403         | `forbidden`           | Authenticated but insufficient permissions        |
| 404         | `not_found`           | Resource does not exist                           |
| 409         | `conflict`            | Resource already exists (e.g., duplicate urlname) |
| 410         | `gone`                | Resource has been deleted                         |
| 422         | `unprocessable`       | Validation error on submitted data                |
| 429         | `rate_limited`        | Too many requests — see Rate Limiting section     |
| 500         | `internal_error`      | Unexpected server error                           |
| 503         | `service_unavailable` | API temporarily unavailable                       |

---

## Rate Limiting

Meetup API enforces rate limits per access token.

| Tier       | Requests per Hour |
|------------|-------------------|
| Standard   | 200               |
| Partner    | 1,000             |
| Enterprise | 10,000            |

Rate limit info is included in every response header:

```
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 143
X-RateLimit-Reset: 1716224400
```

When rate limited, the API returns `429 Too Many Requests`. Back off and retry after the `X-RateLimit-Reset` timestamp (
Unix epoch seconds).

---

## Webhooks

Meetup supports webhooks for real-time event notifications pushed to your server.

### Registering a Webhook

```
POST /webhooks
```

**Request Body:**

| Field  | Type   | Required | Description                                                         |
|--------|--------|----------|---------------------------------------------------------------------|
| url    | string | Yes      | HTTPS endpoint to receive POST events                               |
| events | array  | Yes      | Array of event types to subscribe to                                |
| secret | string | No       | Secret key — Meetup signs payloads with `X-Meetup-Signature` header |

**Supported Event Types:**

| Event Type          | Trigger                        |
|---------------------|--------------------------------|
| `group.created`     | New group created              |
| `group.updated`     | Group info updated             |
| `group.deleted`     | Group deleted                  |
| `event.created`     | New event published            |
| `event.updated`     | Event details changed          |
| `event.cancelled`   | Event cancelled                |
| `event.deleted`     | Event deleted                  |
| `rsvp.created`      | New RSVP submitted             |
| `rsvp.updated`      | RSVP response changed          |
| `rsvp.deleted`      | RSVP cancelled                 |
| `member.joined`     | New member joined a group      |
| `member.left`       | Member left a group            |
| `comment.created`   | New comment posted on an event |
| `payment.completed` | Payment successfully processed |
| `payment.refunded`  | Refund issued                  |

**Example Webhook Payload (`event.created`):**

```json
{
  "webhook_id": "wh_001",
  "type": "event.created",
  "timestamp": 1715200000000,
  "data": {
    "event": {
      "id": "evt_abc123",
      "name": "Python Workshop: FastAPI",
      "time": 1718640000000,
      "group": {
        "id": 1234567,
        "urlname": "nyc-python-developers"
      }
    }
  }
}
```

**Signature Verification (Node.js example):**

```javascript
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
    const hmac = crypto.createHmac('sha256', secret);
    hmac.update(payload);
    const computed = 'sha256=' + hmac.digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(computed),
        Buffer.from(signature)
    );
}

// In your webhook handler:
const isValid = verifySignature(
    req.rawBody,
    req.headers['x-meetup-signature'],
    process.env.WEBHOOK_SECRET
);
```

---

### List Webhooks

```
GET /webhooks
```

### Delete a Webhook

```
DELETE /webhooks/{webhookId}
```

---

*Documentation version: 3.0 | Last updated: May 2026*
