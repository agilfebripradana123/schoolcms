# Teacher API Documentation

> **Version:** Phase 10 — Integration Contract
> **Last Updated:** August 2026
> **Source of Truth:** Source code audit (routes/api.php, TeacherController.php, Request classes, Models, Export/Import)
> **Database:** Legacy MySQL 8.4 (`schoolcms_db`) — CodeIgniter 4 origin

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [Authentication](#3-authentication)
4. [Authorization](#4-authorization)
5. [API Endpoints](#5-api-endpoints)
6. [Request Validation](#6-request-validation)
7. [Response Contract](#7-response-contract)
8. [Error Handling](#8-error-handling)
9. [Search & Filtering](#9-search--filtering)
10. [Pagination](#10-pagination)
11. [Teacher Data Contract](#11-teacher-data-contract)
12. [Relationships](#12-relationships)
13. [Soft Delete](#13-soft-delete)
14. [Excel Import](#14-excel-import)
15. [Excel Export](#15-excel-export)
16. [Frontend Integration](#16-frontend-integration)
17. [Security Rules](#17-security-rules)
18. [Legacy Database Rules](#18-legacy-database-rules)
19. [Testing Contract](#19-testing-contract)
20. [Developer Integration Checklist](#20-developer-integration-checklist)

---

## 1. Overview

The Teacher API is a RESTful JSON API built with Laravel 12 and Laravel Sanctum. It provides CRUD operations, Excel import, and Excel export for the `teachers` table in a legacy CodeIgniter 4 MySQL database.

**Base URL:** `/api`

**Authentication:** Laravel Sanctum (personal access tokens via `Bearer` header)

**Content-Type:** `application/json` (API), `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (export)

**Key Packages:**
- `laravel/sanctum` — Token-based authentication
- `maatwebsite/excel` 4.0 — Excel import/export
- `phpoffice/phpspreadsheet` 5.9 — Spreadsheet engine

**Legacy Context:** The `schoolcms_db` database was originally created by a CodeIgniter 4 application. It is the production database. No Laravel migrations or seeds modify this database. All schema and data changes must go through the legacy application or direct SQL with DBA approval.

---

## 2. Architecture

```
Frontend (SPA/Mobile)
    |
    v
+-------------------------------------------+
|  Laravel 12 API (PHP 8.5)                |
|                                           |
|  routes/api.php                           |
|    +-- auth:sanctum middleware             |
|    +-- role:Admin,Administrator middleware |
|                                           |
|  TeacherController.php                    |
|    +-- TeacherIndexRequest (validation)    |
|    +-- StoreTeacherRequest  (validation)   |
|    +-- UpdateTeacherRequest (validation)   |
|    +-- TeacherExportRequest (validation)   |
|    +-- TeacherImportRequest (validation)   |
|    +-- TeacherResource      (response)     |
|    +-- TeachersExport       (Excel export) |
|    +-- TeachersImport       (Excel import) |
|                                           |
|  Teacher Model (SoftDeletes)              |
|    +-- User           (BelongsTo)         |
|    +-- Classes        (HasMany)           |
|    +-- ClassSubjects  (HasMany)           |
+-------------------------------------------+
    |
    v
+-------------------------------------------+
|  Legacy MySQL 8.4 — schoolcms_db          |
|    +-- teachers   (19 records)            |
|    +-- users      (22 records)            |
|    +-- roles      (4 records)             |
|    +-- classes    (3 records)             |
|    +-- subjects   (17 records)            |
+-------------------------------------------+
```

**File Locations:**

| File | Path |
|------|------|
| Routes | `routes/api.php` |
| Controller | `app/Http/Controllers/Api/TeacherController.php` |
| Model | `app/Models/Teacher.php` |
| Resource | `app/Http/Resources/TeacherResource.php` |
| Index Request | `app/Http/Requests/Api/TeacherIndexRequest.php` |
| Store Request | `app/Http/Requests/Api/StoreTeacherRequest.php` |
| Update Request | `app/Http/Requests/Api/UpdateTeacherRequest.php` |
| Export Request | `app/Http/Requests/Api/TeacherExportRequest.php` |
| Import Request | `app/Http/Requests/Api/TeacherImportRequest.php` |
| Export Class | `app/Exports/TeachersExport.php` |
| Import Class | `app/Imports/TeachersImport.php` |
| Role Middleware | `app/Http/Middleware/RoleMiddleware.php` |
| Auth Controller | `app/Http/Controllers/Api/AuthController.php` |

---

## 3. Authentication

All Teacher API endpoints require authentication via Laravel Sanctum.

**Login Endpoint:** `POST /api/login`

**Request:**

```http
POST /api/login
Content-Type: application/json

{
    "login": "admin@example.com",
    "password": "secret"
}
```

The `login` field accepts either `email` or `username`.

**Login Response (200):**

```json
{
    "message": "Login berhasil.",
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Admin User",
        "username": "admin",
        "email": "admin@example.com",
        "photo": null,
        "is_active": true,
        "role": "Admin"
    }
}
```

**Login Errors:**

| Status | Message | Condition |
|--------|---------|-----------|
| 401 | `Username atau Email tidak ditemukan.` | No user matches email/username |
| 401 | `Password salah.` | Password hash does not match |
| 403 | `Akun Anda tidak aktif.` | `is_active` is false |

**Using the Token:**

All subsequent requests must include the token in the `Authorization` header:

```http
Authorization: Bearer {token}
```

**Without Token:** All authenticated endpoints return `401 Unauthorized`:

```json
{
    "message": "Unauthenticated."
}
```

**Token Lifecycle:**

- Tokens are created via `POST /api/login`
- Current token can be deleted via `POST /api/logout`
- Authenticated user info via `GET /api/me`

---

## 4. Authorization

Authorization is role-based via `RoleMiddleware`. Four roles exist in the legacy database:

| Role | Read (Index/Show/Export) | Write (Store/Update/Delete) | Import |
|------|--------------------------|----------------------------|--------|
| Admin | Yes | Yes | Yes |
| Administrator | Yes | Yes | Yes |
| Guru | Yes | No | No |
| Siswa | Yes | No | No |

**Implementation:** The `role` middleware is registered in `bootstrap/app.php` and applied via route group in `routes/api.php`:

```php
Route::middleware('role:Admin,Administrator')->group(function () {
    // POST, PUT, PATCH, DELETE, IMPORT
});
```

**403 Response:**

```json
{
    "success": false,
    "message": "Unauthorized",
    "data": null
}
```

Note: The middleware message is `"Unauthorized"` (HTTP 403), not `"Forbidden"`. This is the actual implementation behavior.

---

## 5. API Endpoints

### 5.1 List Teachers

```
GET /api/teachers
```

**Authentication:** `auth:sanctum` — All authenticated roles

**Query Parameters:**

| Parameter | Type | Required | Default | Validation | Description |
|-----------|------|----------|---------|------------|-------------|
| `page` | integer | No | 1 | `nullable, integer, min:1` | Page number |
| `per_page` | integer | No | 10 | `nullable, integer, min:1, max:100` | Results per page |
| `search` | string | No | — | `nullable, string, max:100` | Search across teacher_code, nip, full_name, phone |
| `gender` | string | No | — | `nullable, string, in:L,P` | L=Laki-laki, P=Perempuan |
| `employment_status` | string | No | — | `nullable, string, max:50` | Free text filter |
| `is_active` | string | No | — | `nullable, in:0,1` | 0=inactive, 1=active |

**Response (200):**

```json
{
    "success": true,
    "message": "Teachers retrieved successfully",
    "data": [
        {
            "id": 1,
            "user_id": 5,
            "teacher_code": "GUR001",
            "nip": "198501012010011001",
            "full_name": "Eko Setiawan",
            "prefix_title": "S.Pd",
            "suffix_title": null,
            "phone": "081234567890",
            "email": "eko@sekolah.sch.id",
            "last_education": "S2",
            "major": "Pendidikan Matematika",
            "employment_status": "PNS",
            "join_date": "2010-07-15",
            "photo": null,
            "is_active": true,
            "address": "Jl. Merdeka No. 10",
            "gender": "L",
            "birth_place": "Surabaya",
            "birth_date": "1985-01-15",
            "religion": "Islam",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-06-15T10:30:00.000000Z",
            "user": {
                "id": 5,
                "role_id": 2,
                "name": "Eko Setiawan",
                "username": "eko",
                "email": "eko@sekolah.sch.id",
                "photo": null,
                "is_active": true
            },
            "classes": [],
            "class_subjects": []
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 19,
        "last_page": 2
    }
}
```

**Behavior:**

- Eager loads `user` relationship only
- Soft-deleted teachers are excluded (via `whereNull('deleted_at')` scope)
- Ordered by `id` descending
- Email is deliberately excluded from search (see [Section 9](#9-search--filtering))

### 5.2 Show Teacher

```
GET /api/teachers/{teacher}
```

**Authentication:** `auth:sanctum` — All authenticated roles

**Path Parameter:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `teacher` | integer | Teacher ID |

**Response (200):** Same structure as index items but as a single object. Additionally loads `classes` and `classSubjects` relationships:

```json
{
    "success": true,
    "message": "Teacher retrieved successfully",
    "data": {
        "id": 1,
        "user_id": 5,
        "teacher_code": "GUR001",
        "nip": "198501012010011001",
        "full_name": "Eko Setiawan",
        "prefix_title": "S.Pd",
        "suffix_title": null,
        "phone": "081234567890",
        "email": "eko@sekolah.sch.id",
        "last_education": "S2",
        "major": "Pendidikan Matematika",
        "employment_status": "PNS",
        "join_date": "2010-07-15",
        "photo": null,
        "is_active": true,
        "address": "Jl. Merdeka No. 10",
        "gender": "L",
        "birth_place": "Surabaya",
        "birth_date": "1985-01-15",
        "religion": "Islam",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-06-15T10:30:00.000000Z",
        "user": { "..." : "..." },
        "classes": [
            {
                "id": 1,
                "name": "X-A",
                "teacher_id": 1,
                "level": "X",
                "academic_year": "2025/2026"
            }
        ],
        "class_subjects": [
            {
                "id": 1,
                "class_id": 1,
                "subject_id": 3,
                "teacher_id": 1
            }
        ]
    }
}
```

**Behavior:**

- Eager loads `user`, `classes`, and `classSubjects` relationships
- Only returns non-soft-deleted teachers (explicit `whereNull('deleted_at')`)

**Error (404):**

```json
{
    "success": false,
    "message": "Teacher not found",
    "data": null
}
```

Returned when:
- Teacher ID does not exist
- Teacher is soft-deleted (`deleted_at IS NOT NULL`)

### 5.3 Create Teacher

```
POST /api/teachers
```

**Authentication:** `auth:sanctum` + `role:Admin,Administrator`

**Request Body:**

| Field | Type | Required | Nullable | Max | Validation | Description |
|-------|------|----------|----------|-----|------------|-------------|
| `teacher_code` | string | **Yes** | No | 20 | `unique:teachers,teacher_code` | Unique teacher identifier |
| `nip` | string | **Yes** | No | 30 | `unique:teachers,nip` | Nomor Induk Pegawai |
| `gender` | string | **Yes** | No | — | `in:L,P` | L or P |
| `is_active` | boolean | **Yes** | No | — | — | Active status |
| `full_name` | string | No | Yes | 150 | — | Full name |
| `prefix_title` | string | No | Yes | 50 | — | Title prefix |
| `suffix_title` | string | No | Yes | 50 | — | Title suffix |
| `phone` | string | No | Yes | 20 | — | Phone number |
| `email` | string | No | Yes | 150 | `email` | Email address |
| `last_education` | string | No | Yes | 50 | — | Education level |
| `major` | string | No | Yes | 100 | — | Field of study |
| `employment_status` | string | No | Yes | 50 | — | PNS, Honorer, PPPK |
| `join_date` | string | No | Yes | — | `date` | Join date (Y-m-d) |
| `photo` | string | No | Yes | 255 | — | Photo file path |
| `address` | string | No | Yes | — | — | Full address |
| `birth_place` | string | No | Yes | 100 | — | Place of birth |
| `birth_date` | string | No | Yes | — | `date` | Date of birth (Y-m-d) |
| `religion` | string | No | Yes | 30 | — | Religion |
| `user_id` | integer | No | Yes | — | `exists:users,id` | Linked user account |

**Example Request:**

```json
{
    "teacher_code": "GUR020",
    "nip": "199001012020012001",
    "full_name": "Budi Santoso",
    "gender": "L",
    "is_active": true,
    "phone": "081234567891",
    "email": "budi@sekolah.sch.id",
    "employment_status": "Honorer",
    "join_date": "2020-08-01"
}
```

**Response (201):** TeacherResource with all fields, `user` will be null if `user_id` not provided.

**Validation Error (422):**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "teacher_code": ["The teacher code has already been taken."]
    }
}
```

### 5.4 Update Teacher (PUT)

```
PUT /api/teachers/{teacher}
```

**Authentication:** `auth:sanctum` + `role:Admin,Administrator`

**Path Parameter:** `teacher` — Teacher ID (integer)

**Request Body:** Same fields as Create Teacher, but all fields use `sometimes` rule. When a field is present, it must pass validation. When absent, the field retains its current value.

**Unique Validation Behavior:** `teacher_code` and `nip` uniqueness checks use `Rule::unique()->ignore($teacherId)` — you can submit the existing value without triggering a duplicate error.

**Response (200):** TeacherResource JSON.

**Error (404):** Teacher not found or soft-deleted.

### 5.5 Partial Update Teacher (PATCH)

```
PATCH /api/teachers/{teacher}
```

**Authentication:** `auth:sanctum` + `role:Admin,Administrator`

**Behavior:** PATCH uses the exact same controller method and `UpdateTeacherRequest` as PUT. The difference is semantic — PATCH allows sending only the fields you want to change.

**Example — Update only phone number:**

```json
{
    "phone": "081234567890"
}
```

All other fields remain unchanged. When a field is absent from the request body, the `sometimes` rule skips validation for that field.

**Response (200):** Same format as PUT response.

**Error (404):** Teacher not found or soft-deleted.

### 5.6 Delete Teacher

```
DELETE /api/teachers/{teacher}
```

**Authentication:** `auth:sanctum` + `role:Admin,Administrator`

**Path Parameter:** `teacher` — Teacher ID (integer)

**Response (200):**

```json
{
    "success": true,
    "message": "Teacher deleted successfully",
    "data": null
}
```

**Behavior:**

- Uses Laravel SoftDeletes — `deleted_at` timestamp is set, record is NOT physically removed
- Soft-deleted teacher is excluded from index, show, and export queries
- Subsequent DELETE on the same teacher returns 404
- Classes referencing this teacher have `teacher_id` set to NULL (ON DELETE SET NULL)
- ClassSubjects referencing this teacher are deleted (ON DELETE CASCADE)

**Error (404):** Teacher not found or already soft-deleted.

### 5.7 Import Teachers

```
POST /api/teachers/import
```

**Authentication:** `auth:sanctum` + `role:Admin,Administrator`

**Content-Type:** `multipart/form-data`

**Request:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file` | file | **Yes** | Excel file (.xlsx or .xls), max 5120 KB |

**Example:**

```http
POST /api/teachers/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: teachers_import.xlsx
```

**Response — Full Success (200):**

```json
{
    "success": true,
    "message": "Teachers imported successfully",
    "data": {
        "total_rows": 5,
        "imported": 5,
        "failed": 0,
        "errors": []
    }
}
```

**Response — Partial Success (422):**

```json
{
    "success": false,
    "message": "Teacher import completed with errors",
    "data": {
        "total_rows": 5,
        "imported": 3,
        "failed": 2,
        "errors": [
            {
                "row": 4,
                "field": "teacher_code",
                "message": "Kode Guru already exists in database"
            },
            {
                "row": 5,
                "field": "nip",
                "message": "NIP duplicate in file"
            }
        ]
    }
}
```

**Response — Empty File (200):**

```json
{
    "success": true,
    "message": "Teachers imported successfully",
    "data": {
        "total_rows": 0,
        "imported": 0,
        "failed": 0,
        "errors": []
    }
}
```

**Response — Invalid Headers (422):**

```json
{
    "success": false,
    "message": "Invalid Excel header",
    "data": null
}
```

**Response — Parse Error (422):**

```json
{
    "success": false,
    "message": "Failed to process Excel file",
    "data": null
}
```

**Behavior:**

- Imported rows set `user_id = null`, `prefix_title = null`, `suffix_title = null`, `photo = null`, `is_active = true`
- Duplicate detection is case-insensitive (compared uppercase)
- Duplicate detection runs against both existing database records AND within the same file
- Valid rows are imported in a database transaction
- Dates support: `Y-m-d`, `DD/MM/YYYY`, Excel serial numbers, and Carbon-parseable strings
- See [Section 14](#14-excel-import) for full format details

### 5.8 Export Teachers

```
GET /api/teachers/export
```

**Authentication:** `auth:sanctum` — All authenticated roles

**Query Parameters:**

| Parameter | Type | Required | Validation | Description |
|-----------|------|----------|------------|-------------|
| `search` | string | No | `nullable, string, max:100` | Search teacher_code, nip, full_name, phone |
| `gender` | string | No | `nullable, string, in:L,P` | Filter by gender |
| `employment_status` | string | No | `nullable, string, max:50` | Filter by employment status |
| `is_active` | string | No | `nullable, in:0,1` | Filter by active status |

**Response:** Binary Excel file download (.xlsx)

- **Content-Type:** `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- **Content-Disposition:** `attachment; filename=teachers.xlsx`
- **File size:** Varies based on data

**Behavior:**

- Query-based export (`FromQuery` interface) — memory efficient for large datasets
- Only exports non-soft-deleted teachers
- Ordered by `id` ascending
- Same search/filter logic as the index endpoint
- Uses `ShouldAutoSize` for auto-sized columns

**Error (422):** Invalid filter values.

See [Section 15](#15-excel-export) for column mapping details.

---

## 6. Request Validation

### Validation Error Format

All validation errors use a consistent format (HTTP 422):

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": [
            "First validation message",
            "Second validation message"
        ]
    }
}
```

### StoreTeacherRequest Rules

Source: `app/Http/Requests/Api/StoreTeacherRequest.php`

```php
'user_id'           => ['nullable', 'integer', 'exists:users,id'],
'teacher_code'      => ['required', 'string', 'max:20', 'unique:teachers,teacher_code'],
'nip'               => ['required', 'string', 'max:30', 'unique:teachers,nip'],
'full_name'         => ['nullable', 'string', 'max:150'],
'prefix_title'      => ['nullable', 'string', 'max:50'],
'suffix_title'      => ['nullable', 'string', 'max:50'],
'phone'             => ['nullable', 'string', 'max:20'],
'email'             => ['nullable', 'email', 'max:150'],
'last_education'    => ['nullable', 'string', 'max:50'],
'major'             => ['nullable', 'string', 'max:100'],
'employment_status' => ['nullable', 'string', 'max:50'],
'join_date'         => ['nullable', 'date'],
'photo'             => ['nullable', 'string', 'max:255'],
'is_active'         => ['required', 'boolean'],
'address'           => ['nullable', 'string'],
'gender'            => ['required', 'in:L,P'],
'birth_place'       => ['nullable', 'string', 'max:100'],
'birth_date'        => ['nullable', 'date'],
'religion'          => ['nullable', 'string', 'max:30'],
```

### UpdateTeacherRequest Rules

Source: `app/Http/Requests/Api/UpdateTeacherRequest.php`

All fields use `sometimes` — only validated when present in request:

```php
'user_id'           => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
'teacher_code'      => ['sometimes', 'required', 'string', 'max:20',
                        Rule::unique('teachers', 'teacher_code')->ignore($teacherId)],
'nip'               => ['sometimes', 'required', 'string', 'max:30',
                        Rule::unique('teachers', 'nip')->ignore($teacherId)],
'full_name'         => ['sometimes', 'nullable', 'string', 'max:150'],
// ... all other fields use 'sometimes' prefix
'gender'            => ['sometimes', 'required', 'in:L,P'],
'is_active'         => ['sometimes', 'required', 'boolean'],
```

### TeacherIndexRequest Rules

Source: `app/Http/Requests/Api/TeacherIndexRequest.php`

```php
'page'              => ['nullable', 'integer', 'min:1'],
'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
'search'            => ['nullable', 'string', 'max:100'],
'gender'            => ['nullable', 'string', 'in:L,P'],
'employment_status' => ['nullable', 'string', 'max:50'],
'is_active'         => ['nullable', 'in:0,1'],
```

### TeacherExportRequest Rules

Source: `app/Http/Requests/Api/TeacherExportRequest.php`

```php
'search'            => ['nullable', 'string', 'max:100'],
'gender'            => ['nullable', 'string', 'in:L,P'],
'employment_status' => ['nullable', 'string', 'max:50'],
'is_active'         => ['nullable', 'in:0,1'],
```

### TeacherImportRequest Rules

Source: `app/Http/Requests/Api/TeacherImportRequest.php`

```php
'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
```

---

## 7. Response Contract

### Standard Success Response (Single Resource)

```json
{
    "success": true,
    "message": "Operation successful message",
    "data": { "..." : "..." }
}
```

### Standard Success Response (List with Pagination)

```json
{
    "success": true,
    "message": "Teachers retrieved successfully",
    "data": [ "..." ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 19,
        "last_page": 2
    }
}
```

### Standard Error Response

```json
{
    "success": false,
    "message": "Error description",
    "data": null
}
```

### Teacher Resource Fields

Source: `app/Http/Resources/TeacherResource.php`

| Field | Type | Format | Notes |
|-------|------|--------|-------|
| `id` | integer | — | Auto-increment primary key |
| `user_id` | integer/null | — | FK to users table |
| `teacher_code` | string | — | Max 20 chars, unique |
| `nip` | string | — | Max 30 chars, unique |
| `full_name` | string/null | — | Max 150 chars |
| `prefix_title` | string/null | — | Max 50 chars |
| `suffix_title` | string/null | — | Max 50 chars |
| `phone` | string/null | — | Max 20 chars |
| `email` | string/null | — | Max 150 chars |
| `last_education` | string/null | — | Max 50 chars |
| `major` | string/null | — | Max 100 chars |
| `employment_status` | string/null | — | Max 50 chars |
| `join_date` | string/null | `Y-m-d` | Date only |
| `photo` | string/null | — | File path |
| `is_active` | boolean | — | true/false |
| `address` | string/null | — | Text |
| `gender` | string | `L` or `P` | — |
| `birth_place` | string/null | — | Max 100 chars |
| `birth_date` | string/null | `Y-m-d` | Date only |
| `religion` | string/null | — | Max 30 chars |
| `created_at` | string/null | ISO 8601 | `2024-01-01T00:00:00.000000Z` |
| `updated_at` | string/null | ISO 8601 | `2024-01-01T00:00:00.000000Z` |
| `user` | object/null | — | When loaded (UserResource) |
| `classes` | array | — | When loaded (SchoolClassResource) |
| `class_subjects` | array | — | When loaded (ClassSubjectResource) |

### User Resource Fields (nested in teacher.user)

Source: `app/Http/Resources/UserResource.php`

| Field | Type |
|-------|------|
| `id` | integer |
| `role_id` | integer |
| `name` | string |
| `username` | string |
| `email` | string |
| `photo` | string/null |
| `is_active` | boolean |

---

## 8. Error Handling

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful GET, PUT, PATCH, DELETE |
| 201 | Created | Successful POST (create) |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Authenticated but role not allowed |
| 404 | Not Found | Resource doesn't exist or is soft-deleted |
| 422 | Validation Failed | Request data fails validation |
| 500 | Server Error | Unexpected server failure |

### Error Response Examples

**401 — Unauthenticated:**

```json
{
    "message": "Unauthenticated."
}
```

**403 — Role Middleware:**

```json
{
    "success": false,
    "message": "Unauthorized",
    "data": null
}
```

**404 — Teacher Not Found:**

```json
{
    "success": false,
    "message": "Teacher not found",
    "data": null
}
```

**422 — Validation:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "teacher_code": ["The teacher code has already been taken."],
        "gender": ["The selected gender is invalid."]
    }
}
```

**422 — Import Error:**

```json
{
    "success": false,
    "message": "Failed to process Excel file",
    "data": null
}
```

---

## 9. Search & Filtering

### Search

**Parameters:** `search`

**Fields searched (case-insensitive LIKE):**

1. `teachers.teacher_code`
2. `teachers.nip`
3. `teachers.full_name`
4. `teachers.phone`

**Email is deliberately excluded from search.**

**Technical reason:** Legacy email addresses use the domain `@sekolah.sch.id`. The word "sekolah" appears in every teacher's email domain. A `LIKE '%sekolah%'` query on the `email` column would match ALL teachers, producing a false-positive result that returns the entire dataset.

**Search implementation:**

```php
$query->where(function ($q) use ($search) {
    $q->where('teacher_code', 'like', "%{$search}%")
        ->orWhere('nip', 'like', "%{$search}%")
        ->orWhere('full_name', 'like', "%{$search}%")
        ->orWhere('phone', 'like', "%{$search}%");
});
```

### Filtering

**Gender filter:** Exact match on `gender` column (`L` or `P`).

**Employment status filter:** Exact match on `employment_status` column (free text — values include `PNS`, `Honorer`, `PPPK`).

**Active filter:** Exact match on `is_active` column (`0` or `1`).

**Combining:** All filters are AND-combined. Search + gender + employment_status + is_active can all be used together.

---

## 10. Pagination

**Default per page:** 10

**Maximum per page:** 100

**Minimum per page:** 1

**Page minimum:** 1

**Order:** `id` descending (newest first)

**Implementation:** Laravel's `paginate()` method.

**Meta fields:**

| Field | Description |
|-------|-------------|
| `current_page` | Current page number |
| `per_page` | Items per page |
| `total` | Total matching records |
| `last_page` | Total number of pages |

---

## 11. Teacher Data Contract

Complete field mapping between API, database, and validation:

| API Field | Database Column | DB Type | DB Nullable | API Required | Max Length | Validation |
|-----------|----------------|---------|-------------|--------------|------------|------------|
| `id` | `teachers.id` | int unsigned | No (PK) | Auto | — | Auto-increment |
| `user_id` | `teachers.user_id` | int unsigned | Yes (FK) | No | — | `exists:users,id` |
| `teacher_code` | `teachers.teacher_code` | varchar(20) | Yes (DB) | **Yes (API)** | 20 | `unique:teachers,teacher_code` |
| `nip` | `teachers.nip` | varchar(30) | Yes (DB) | **Yes (API)** | 30 | `unique:teachers,nip` |
| `full_name` | `teachers.full_name` | varchar(150) | Yes | No | 150 | — |
| `prefix_title` | `teachers.prefix_title` | varchar(50) | Yes | No | 50 | — |
| `suffix_title` | `teachers.suffix_title` | varchar(50) | Yes | No | 50 | — |
| `phone` | `teachers.phone` | varchar(20) | Yes | No | 20 | — |
| `email` | `teachers.email` | varchar(150) | Yes | No | 150 | `email` |
| `last_education` | `teachers.last_education` | varchar(50) | Yes | No | 50 | — |
| `major` | `teachers.major` | varchar(100) | Yes | No | 100 | — |
| `employment_status` | `teachers.employment_status` | varchar(50) | Yes | No | 50 | — |
| `join_date` | `teachers.join_date` | datetime | Yes | No | — | `date` |
| `photo` | `teachers.photo` | varchar(255) | Yes | No | 255 | — |
| `is_active` | `teachers.is_active` | tinyint(1) | Yes | **Yes (API)** | — | `boolean` |
| `address` | `teachers.address` | text | Yes | No | — | — |
| `gender` | `teachers.gender` | varchar(1) | Yes | **Yes (API)** | — | `in:L,P` |
| `birth_place` | `teachers.birth_place` | varchar(100) | Yes | No | 100 | — |
| `birth_date` | `teachers.birth_date` | datetime | Yes | No | — | `date` |
| `religion` | `teachers.religion` | varchar(30) | Yes | No | 30 | — |
| `created_at` | `teachers.created_at` | datetime | Yes (auto) | — | — | Auto-managed |
| `updated_at` | `teachers.updated_at` | datetime | Yes (auto) | — | — | Auto-managed |
| `deleted_at` | `teachers.deleted_at` | datetime | Yes (auto) | — | — | SoftDeletes |

**Important Note:** Some fields are nullable in the database but required by API validation (`teacher_code`, `nip`, `gender`, `is_active`). This means the API enforces stricter rules than the database schema. Legacy data may contain rows where these fields are NULL — those rows were created by the CI4 application and predate the Laravel API.

---

## 12. Relationships

### Teacher -> User (BelongsTo)

```
teachers.user_id  ->  users.id
```

- Loaded in: index (list), show, store response, update response
- FK behavior: `ON DELETE CASCADE ON UPDATE SET NULL`
- When `user_id` is NULL, the `user` field in response is `null`

### Teacher -> Classes (HasMany)

```
classes.teacher_id  ->  teachers.id
```

- Loaded in: show only
- FK behavior: `ON DELETE SET NULL ON UPDATE CASCADE`
- When a teacher is deleted, `classes.teacher_id` is set to NULL

### Teacher -> ClassSubjects (HasMany)

```
class_subjects.teacher_id  ->  teachers.id
```

- Loaded in: show only
- FK behavior: `ON DELETE CASCADE ON UPDATE SET NULL`
- When a teacher is deleted, related class_subjects rows are deleted

### Teacher -> Subject (Indirect)

```
Teacher -> ClassSubject -> Subject
```

There is no direct Teacher -> Subject relationship. Subjects are linked through `class_subjects`:

```
class_subjects.teacher_id  ->  teachers.id
class_subjects.subject_id  ->  subjects.id
```

---

## 13. Soft Delete

The `teachers` table uses Laravel's `SoftDeletes` trait.

**Behavior:**

- `DELETE /api/teachers/{id}` sets `deleted_at = NOW()` — record is NOT removed
- Soft-deleted teachers are excluded from all queries (`WHERE deleted_at IS NULL`)
- `GET /api/teachers` — soft-deleted teachers not shown
- `GET /api/teachers/{id}` — soft-deleted teachers return 404
- `GET /api/teachers/export` — soft-deleted teachers not exported
- Second `DELETE` on same teacher returns 404

**Database Impact:**

- Physical record remains in `teachers` table with `deleted_at` timestamp
- All other columns retain their values
- No data is lost

---

## 14. Excel Import

### Excel Template Format

The import file must be `.xlsx` or `.xls` format, max 5120 KB (5 MB).

**Required Headers (Row 1):**

| Column | Header Name |
|--------|-------------|
| A | Kode Guru |
| B | NIP |
| C | Nama Lengkap |
| D | Jenis Kelamin |
| E | Tempat Lahir |
| F | Tanggal Lahir |
| G | No. HP |
| H | Email |
| I | Agama |
| J | Alamat |
| K | Pendidikan Terakhir |
| L | Jurusan |
| M | Status Kepegawaian |
| N | Tanggal Bergabung |

Headers must match exactly (case-sensitive, trimmed). Any mismatch results in "Invalid Excel header" error.

### Column Mapping

| Excel Column | Database Field | Required | Format |
|-------------|---------------|----------|--------|
| Kode Guru | `teacher_code` | Yes | String, max 20 |
| NIP | `nip` | Yes | String, max 30 |
| Nama Lengkap | `full_name` | No | String, max 150 |
| Jenis Kelamin | `gender` | Yes | `L` or `P` |
| Tempat Lahir | `birth_place` | No | String, max 100 |
| Tanggal Lahir | `birth_date` | No | Date (see formats below) |
| No. HP | `phone` | No | String, max 20 |
| Email | `email` | No | Valid email, max 150 |
| Agama | `religion` | No | String, max 30 |
| Alamat | `address` | No | Text |
| Pendidikan Terakhir | `last_education` | No | String, max 50 |
| Jurusan | `major` | No | String, max 100 |
| Status Kepegawaian | `employment_status` | No | String, max 50 |
| Tanggal Bergabung | `join_date` | No | Date (see formats below) |

### Date Parsing

The import supports multiple date formats:

1. **Y-m-d** — `2024-01-15` (ISO format)
2. **DD/MM/YYYY** — `15/01/2024`
3. **Excel serial number** — `45307` (converts via PhpSpreadsheet)
4. **Carbon-parseable strings** — Any format recognized by `Carbon\Carbon::parse()` (year must be 1900-2100)

### Validation Per Row

Each data row is validated independently. Errors do not stop other rows from being processed.

**Row validation rules:**

| Field | Rule | Error Message |
|-------|------|---------------|
| `teacher_code` | Required, max 20 | Kode Guru is required / must not exceed 20 characters |
| `nip` | Required, max 30 | NIP is required / must not exceed 30 characters |
| `gender` | Must be L or P | Jenis Kelamin must be L or P |
| `birth_date` | Valid date if provided | Tanggal Lahir is not a valid date |
| `join_date` | Valid date if provided | Tanggal Bergabung is not a valid date |
| `email` | Valid email if provided | Email is not valid |
| `full_name` | Max 150 | Nama Lengkap must not exceed 150 characters |
| `phone` | Max 20 | No. HP must not exceed 20 characters |
| `email` | Max 150 | Email must not exceed 150 characters |
| `religion` | Max 30 | Agama must not exceed 30 characters |
| `last_education` | Max 50 | Pendidikan Terakhir must not exceed 50 characters |
| `major` | Max 100 | Jurusan must not exceed 100 characters |
| `employment_status` | Max 50 | Status Kepegawaian must not exceed 50 characters |
| `birth_place` | Max 100 | Tempat Lahir must not exceed 100 characters |

### Duplicate Detection

**Case-insensitive** (both values uppercased before comparison):

1. **Against database:** `teacher_code` and `nip` are checked against existing non-deleted records
2. **Within file:** Duplicate `teacher_code` or `nip` within the same file are rejected

Error messages:
- `Kode Guru already exists in database`
- `NIP already exists in database`
- `Kode Guru duplicate in file`
- `NIP duplicate in file`

### Default Values for Imported Rows

| Field | Default Value |
|-------|---------------|
| `user_id` | `null` |
| `prefix_title` | `null` |
| `suffix_title` | `null` |
| `photo` | `null` |
| `is_active` | `true` |

---

## 15. Excel Export

### Export Behavior

- Uses `FromQuery` interface — builds a single query, streams rows (memory efficient)
- Only exports non-soft-deleted teachers
- Ordered by `id` ascending
- Auto-sized columns via `ShouldAutoSize`

### Search in Export

Same search logic as index endpoint:

| Parameter | Fields Searched |
|-----------|----------------|
| `search` | `teacher_code`, `nip`, `full_name`, `phone` |

Email is NOT included in export search (same rationale as index).

### Column Mapping

Source: `app/Exports/TeachersExport.php` — `headings()` and `map()` methods.

| Column | Heading | Source Field | Format |
|--------|---------|-------------|--------|
| A | Kode Guru | `teacher_code` | string |
| B | NIP | `nip` | string |
| C | Nama Lengkap | `full_name` | string |
| D | Jenis Kelamin | `gender` | `L`/`P` |
| E | Tempat Lahir | `birth_place` | string |
| F | Tanggal Lahir | `birth_date` | `Y-m-d` |
| G | No. HP | `phone` | string |
| H | Email | `email` | string |
| I | Agama | `religion` | string |
| J | Alamat | `address` | string |
| K | Pendidikan Terakhir | `last_education` | string |
| L | Jurusan | `major` | string |
| M | Status Kepegawaian | `employment_status` | string |
| N | Tanggal Bergabung | `join_date` | `Y-m-d` |

**Date fields:** Null dates export as empty cells. Non-null dates are formatted as `Y-m-d`.

---

## 16. Frontend Integration

### Authentication Flow

```
1. POST /api/login
   Request:  { "login": "email_or_username", "password": "secret" }
   Response: { "token": "1|abc...", "user": {...} }

2. Store token securely (localStorage, httpOnly cookie, etc.)

3. All API requests include:
   Authorization: Bearer {token}

4. POST /api/logout (with token) to end session
```

### List Teachers

```http
GET /api/teachers?page=1&per_page=10
Authorization: Bearer {token}
```

### Search Teachers

```http
GET /api/teachers?search=eko
Authorization: Bearer {token}
```

### Filter Teachers

```http
GET /api/teachers?gender=L&employment_status=PNS&is_active=1
Authorization: Bearer {token}
```

### Combined Search + Filter + Pagination

```http
GET /api/teachers?search=eko&gender=L&is_active=1&page=1&per_page=10
Authorization: Bearer {token}
```

### Teacher Detail

```http
GET /api/teachers/1
Authorization: Bearer {token}
```

### Create Teacher

```http
POST /api/teachers
Authorization: Bearer {token}
Content-Type: application/json

{
    "teacher_code": "GUR020",
    "nip": "199001012020012001",
    "full_name": "Budi Santoso",
    "gender": "L",
    "is_active": true
}
```

### Update Teacher (Full)

```http
PUT /api/teachers/20
Authorization: Bearer {token}
Content-Type: application/json

{
    "teacher_code": "GUR020",
    "nip": "199001012020012001",
    "full_name": "Budi Santoso Updated",
    "gender": "L",
    "is_active": true,
    "phone": "081234567892",
    "employment_status": "PNS"
}
```

### Partial Update Teacher

```http
PATCH /api/teachers/20
Authorization: Bearer {token}
Content-Type: application/json

{
    "phone": "081234567890"
}
```

### Delete Teacher

```http
DELETE /api/teachers/20
Authorization: Bearer {token}
```

### Import Teachers

```http
POST /api/teachers/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [Excel file]
```

### Export Teachers

```http
GET /api/teachers/export?gender=L
Authorization: Bearer {token}
```

Response is a file download — handle as blob/stream in the frontend.

---

## 17. Frontend Error Handling

### 401 — Unauthenticated

```javascript
// Token expired, invalid, or missing
// Action: Redirect to login, clear stored token
if (response.status === 401) {
    localStorage.removeItem('token');
    router.push('/login');
}
```

### 403 — Forbidden

```javascript
// Authenticated but role not allowed
// Action: Show "access denied" message
if (response.status === 403) {
    showMessage('Anda tidak memiliki akses untuk melakukan operasi ini.');
}
```

### 404 — Not Found

```javascript
// Teacher not found or soft-deleted
// Action: Show "not found" message
if (response.status === 404) {
    showMessage('Guru tidak ditemukan.');
}
```

### 422 — Validation Error

```javascript
// Show per-field errors
if (response.status === 422) {
    const errors = response.data.errors;
    // errors = { "teacher_code": ["The teacher code has already been taken."] }
    Object.keys(errors).forEach(field => {
        showFieldError(field, errors[field][0]);
    });
}
```

### 500 — Server Error

```javascript
// Action: Show generic error message
if (response.status === 500) {
    showMessage('Terjadi kesalahan server. Silakan coba lagi.');
    // Do NOT expose database internals
}
```

---

## 18. Security Rules

### Authentication

- All Teacher API endpoints require `auth:sanctum`
- Tokens are personal access tokens (not cookie-based)
- Missing token returns HTTP 401

### Authorization

- Write operations (POST, PUT, PATCH, DELETE, IMPORT) require `Admin` or `Administrator` role
- Read operations (GET index, show, export) are available to all authenticated roles
- Unauthorized role returns HTTP 403

### Mass Assignment Protection

The Teacher model uses `$fillable` — only listed fields can be mass-assigned:

```php
protected $fillable = [
    'user_id', 'teacher_code', 'nip', 'full_name', 'prefix_title',
    'suffix_title', 'phone', 'email', 'last_education', 'major',
    'employment_status', 'join_date', 'photo', 'is_active', 'address',
    'gender', 'birth_place', 'birth_date', 'religion',
];
```

Fields NOT in `$fillable` and therefore cannot be mass-assigned:
- `id` (primary key)
- `deleted_at` (SoftDeletes)
- `created_at` / `updated_at` (auto-managed)

### Sensitive Fields

The following fields are NEVER exposed in Teacher API responses:
- `password` (users table — protected by `$hidden` in User model)
- `remember_token` (not in TeacherResource)
- `deleted_at` (not in TeacherResource)

### Import/Export Safety

- Import writes ONLY to the `teachers` table
- Import does NOT modify `users`, `roles`, `classes`, `subjects`, or any other table
- Export uses SELECT queries only — no INSERT, UPDATE, or DELETE operations

---

## 19. Legacy Database Rules

### Absolute Rules

1. **`schoolcms_db` is a legacy database** from a CodeIgniter 4 application
2. **Do NOT run Laravel default migrations** (`php artisan migrate`) on this database
3. **Do NOT recreate** users, teachers, roles, students, classes, or subjects via seeds
4. **Do NOT modify existing foreign keys** between tables
5. **Do NOT change primary key types** (all are `int unsigned`)
6. **Do NOT change `datetime` columns to `timestamp`** — the legacy schema uses `datetime`
7. **Do NOT import production SQL** without DBA review and approval
8. **Do NOT modify the database schema** via migrations — schema changes require direct SQL with approval
9. **Personal access tokens table** (`personal_access_tokens`) was created via raw SQL — required by Sanctum but missing from legacy schema
10. **All timestamps are `datetime`** type, not Laravel's default `timestamp`

### Database Baseline (Phase 9)

| Table | Record Count |
|-------|-------------|
| teachers | 19 |
| users | 22 |
| roles | 4 |
| classes | 3 |
| subjects | 17 |

### Roles in Legacy Database

| ID | Name |
|----|------|
| 1 | Admin |
| 2 | Guru |
| 4 | Administrator |
| 5 | Siswa |

Note: Role IDs are not sequential — this is legacy data behavior.

---

## 20. Developer Integration Checklist

### Before Starting Development

- [ ] Read this documentation completely
- [ ] Verify legacy database connection (`schoolcms_db`)
- [ ] Obtain valid Sanctum token via `POST /api/login`
- [ ] Verify user role is `Admin` or `Administrator` for write operations
- [ ] Test authentication with `GET /api/me`

### For List/Detail Pages

- [ ] Use `GET /api/teachers` with pagination parameters
- [ ] Implement search via `search` query parameter
- [ ] Implement filters via `gender`, `employment_status`, `is_active`
- [ ] Handle `meta` object for pagination UI
- [ ] Note: `email` is NOT included in search
- [ ] Use `GET /api/teachers/{id}` for detail view (includes classes, class_subjects)

### For Create/Edit Forms

- [ ] Required fields: `teacher_code`, `nip`, `gender`, `is_active`
- [ ] Validate `teacher_code` max 20 characters
- [ ] Validate `nip` max 30 characters
- [ ] Gender: only `L` or `P`
- [ ] Handle 422 validation errors per field
- [ ] Use PUT for full update, PATCH for partial update
- [ ] Unique fields auto-exclude current record on update

### For Import Feature

- [ ] Accept `.xlsx` or `.xls` files only
- [ ] Max file size: 5120 KB (5 MB)
- [ ] Excel headers must match exactly (Indonesian column names)
- [ ] Handle partial success responses (some rows imported, some failed)
- [ ] Display per-row error messages from `errors` array
- [ ] Required columns: Kode Guru, NIP, Jenis Kelamin

### For Export Feature

- [ ] `GET /api/teachers/export` returns file download
- [ ] Apply same filters as index (search, gender, employment_status, is_active)
- [ ] Handle as blob/stream response
- [ ] File format: `.xlsx`
- [ ] Email is NOT included in export search

### Security Checklist

- [ ] Never store token in URL or logs
- [ ] Handle 401 globally (token refresh or re-login)
- [ ] Handle 403 (show access denied, not server error)
- [ ] Never expose database error details to users
- [ ] Validate all input on frontend before sending

---

> **End of Teacher API Documentation — Phase 10 Integration Contract**
