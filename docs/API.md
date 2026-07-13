# API Documentation

Base path: `/api`

The API uses JSON request bodies and JSON responses. Authenticated routes require a Sanctum bearer token.

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

### Logout

```http
POST /api/auth/logout
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
- `per_page`

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

The API rejects changes that would leave the system with zero admin users.

### List All Blogs

```http
GET /api/admin/blogs
```

Optional query parameters:

- `status`
- `search`
- `per_page`

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
