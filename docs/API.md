# API Documentation

Base path: `/api`

The API uses JSON request bodies and JSON responses. Authenticated routes require a Sanctum bearer token.

Laravel serves the interactive API documentation at:

```http
GET /docs/api
```

The machine-readable OpenAPI contract is maintained in [openapi.json](openapi.json) and served at:

```http
GET /docs/api/openapi.json
```

```http
Authorization: Bearer <token>
```

## Response Shape

Most endpoints return:

```json
{
  "success": true,
  "message": "Operation result.",
  "data": {}
}
```

Validation failures return Laravel's default validation response with HTTP `422`.

## Health

```http
GET /api/health
```

Response:

```json
{
  "status": "ok"
}
```

## Authentication

### Register

```http
POST /api/auth/register
```

Request:

```json
{
  "name": "Jane Doe",
  "username": "jane-doe",
  "email": "jane@example.com",
  "password": "password123"
}
```

Success: `201 Created`

Legacy route: `POST /api/register`

### Login

```http
POST /api/auth/login
```

Request:

```json
{
  "email": "jane@example.com",
  "password": "password123"
}
```

Success response includes `data.user` and `data.token`.

Legacy route: `POST /api/login`

### Current User

```http
GET /api/auth/me
Authorization: Bearer <token>
```

Legacy route: `GET /api/getUser`

### Update Profile

```http
PATCH /api/auth/me
Authorization: Bearer <token>
```

Request fields are optional. Send only the fields being changed.

```json
{
  "name": "Jane Doe",
  "username": "jane-doe",
  "email": "jane@example.com"
}
```

To change password, include the current password:

```json
{
  "current_password": "old-password123",
  "password": "new-password123"
}
```

Users cannot update their own `role` or `status` through this endpoint.

### Logout

```http
POST /api/auth/logout
Authorization: Bearer <token>
```

## Notifications

Published posts automatically create notifications for every active registered user, including both `admin` and `author` accounts. Suspended users do not receive new notifications.

### List Notifications

```http
GET /api/notifications
Authorization: Bearer <token>
```

Optional query parameters:

- `unread=1`
- `per_page`

### Unread Count

```http
GET /api/notifications/unread-count
Authorization: Bearer <token>
```

### Mark Notification As Read

```http
PATCH /api/notifications/{id}/read
Authorization: Bearer <token>
```

### Mark All Notifications As Read

```http
PATCH /api/notifications/read-all
Authorization: Bearer <token>
```

## Blogs

### List Published Blogs

```http
GET /api/blogs
```

Optional query parameters:

- `search`
- `category`
- `tag`
- `per_page`

Only published blogs are returned.

Legacy route: `GET /api/allBlogs`

### Show Published Blog

```http
GET /api/blogs/{slug-or-id}
```

Draft and archived blogs are not returned by the public endpoint.

### List Current User Blogs

```http
GET /api/me/blogs
Authorization: Bearer <token>
```

Returns the authenticated author's blogs across all statuses.

Legacy route: `GET /api/listBlogs`

### Create Blog

```http
POST /api/blogs
Authorization: Bearer <token>
```

Request:

```json
{
  "title": "Building a Full Blog API",
  "content": "Long-form blog content.",
  "excerpt": "A short summary.",
  "cover_image_url": "https://example.com/image.jpg",
  "status": "published",
  "published_at": "2026-07-13T12:00:00Z",
  "category_name": "Engineering",
  "tags": ["Laravel", "API"]
}
```

To upload a cover image from the client, send the same fields as `multipart/form-data` and include:

- `cover_image`: image file, accepted types `jpg`, `jpeg`, `png`, `webp`, `gif`, maximum 5 MB

If both `cover_image` and `cover_image_url` are sent, the uploaded file is used.

Allowed `status` values:

- `draft`
- `published`
- `archived`

If `status` is `published` and `published_at` is omitted, the API sets `published_at` to the current time.

Legacy route: `POST /api/addBlog`

### Update Blog

```http
PUT /api/blogs/{id}
PATCH /api/blogs/{id}
Authorization: Bearer <token>
```

Only the blog owner can update a blog.

Send `multipart/form-data` with `cover_image` to replace the uploaded cover image. Replacing or deleting a blog removes the previous locally-uploaded cover image from storage.

Legacy route: `PUT /api/editBlog` with `id` in the request body.

### Delete Blog

```http
DELETE /api/blogs/{id}
Authorization: Bearer <token>
```

Only the blog owner can delete a blog.

Legacy route: `POST /api/deleteBlog` with `id` in the request body.

## Categories

### List Categories

```http
GET /api/categories
```

### Create Category

```http
POST /api/categories
Authorization: Bearer <token>
```

Request:

```json
{
  "name": "Engineering",
  "description": "Technical articles and backend design notes."
}
```

## Tags

### List Tags

```http
GET /api/tags
```

### Create Tag

```http
POST /api/tags
Authorization: Bearer <token>
```

Request:

```json
{
  "name": "Laravel"
}
```

## Comments

### List Approved Comments

```http
GET /api/blogs/{id}/comments
```

### Submit Comment

```http
POST /api/blogs/{id}/comments
```

Guest request:

```json
{
  "author_name": "Reader",
  "author_email": "reader@example.com",
  "content": "Helpful article."
}
```

New comments are created with `pending` status.

### Moderate Comment

```http
PATCH /api/comments/{id}
Authorization: Bearer <token>
```

Request:

```json
{
  "status": "approved"
}
```

Allowed statuses:

- `pending`
- `approved`
- `rejected`

Only the blog owner can moderate comments on their blog.

### Delete Comment

```http
DELETE /api/comments/{id}
Authorization: Bearer <token>
```

Only the blog owner can delete comments on their blog.

## Admin

Admin routes require:

```http
Authorization: Bearer <admin-token>
```

The authenticated user must have `role` set to `admin`.

### Dashboard Metrics

```http
GET /api/admin/dashboard
```

Returns user, blog, comment, category, and tag counts.

### List Users

```http
GET /api/admin/users
```

Optional query parameters:

- `role`
- `status`
- `per_page`

### Create User

```http
POST /api/admin/users
```

Request:

```json
{
  "name": "New Author",
  "username": "new-author",
  "email": "new-author@example.com",
  "password": "password123",
  "role": "author",
  "status": "active"
}
```

Allowed roles:

- `author`
- `admin`

If `role` is omitted, the API creates an author.

If `status` is omitted, the API creates an active user.

### Show User Details

```http
GET /api/admin/users/{id}
```

Returns the user, blog/comment counts, recent blogs, and recent comments.

### User History

```http
GET /api/admin/users/{id}/history
```

Optional query parameters:

- `blogs_page`
- `comments_page`
- `per_page`

Returns paginated blogs authored by the user and comments submitted by the user.

### Update User

```http
PATCH /api/admin/users/{id}
```

Request fields are optional. Send only the fields being changed.

```json
{
  "name": "Updated Author",
  "username": "updated-author",
  "email": "updated-author@example.com",
  "password": "new-password123",
  "role": "admin",
  "status": "active"
}
```

Allowed roles:

- `author`
- `admin`

The API rejects updates that would leave the system with zero active admin users.

### Update User Role

```http
PATCH /api/admin/users/{id}/role
```

Request:

```json
{
  "role": "admin"
}
```

Allowed roles:

- `author`
- `admin`

The API rejects changes that would leave the system with zero active admin users.

### Update User Status

```http
PATCH /api/admin/users/{id}/status
```

Request:

```json
{
  "status": "suspended"
}
```

Allowed statuses:

- `active`
- `suspended`

Suspending a user revokes that user's API tokens and blocks future login. The API rejects suspending the authenticated admin's own account or changes that would leave the system with zero active admin users.

### Delete User

```http
DELETE /api/admin/users/{id}
```

Deletes another user as an admin action. The API rejects deleting the authenticated admin's own account.

### List All Blogs

```http
GET /api/admin/blogs
```

Optional query parameters:

- `status`
- `search`
- `per_page`

### Create Blog As Admin

```http
POST /api/admin/blogs
```

Request:

```json
{
  "user_id": 1,
  "title": "Admin Created Post",
  "content": "Long-form blog content.",
  "excerpt": "A short summary.",
  "cover_image_url": "https://example.com/image.jpg",
  "status": "published",
  "category_name": "Operations",
  "tags": ["Admin", "Review"]
}
```

Admin-created posts also accept `multipart/form-data` with a `cover_image` file, using the same image limits as normal blog creation.

If `user_id` is omitted, the authenticated admin becomes the post author.

### Show Any Blog

```http
GET /api/admin/blogs/{id}
```

Returns a blog across any status with author, category, tags, comments, and comment count.

### Update Blog Status

```http
PATCH /api/admin/blogs/{id}/status
```

Request:

```json
{
  "status": "archived"
}
```

### Delete Blog

```http
DELETE /api/admin/blogs/{id}
```

Deletes any blog as an admin action.

### List All Comments

```http
GET /api/admin/comments
```

Optional query parameters:

- `status`
- `per_page`

### Update Comment Status

```http
PATCH /api/admin/comments/{id}
```

Request:

```json
{
  "status": "approved"
}
```

### Delete Comment

```http
DELETE /api/admin/comments/{id}
```

Deletes any comment as an admin action.
