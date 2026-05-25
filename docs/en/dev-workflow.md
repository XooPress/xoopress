# Content Workflow

XooPress provides a content approval workflow system with a **Draft → Pending Review → Approved → Published** state machine.

## Status States

| Status | Constant | Description |
|--------|----------|-------------|
| `draft` | `STATUS_DRAFT` | Initial state for new content |
| `pending_review` | `STATUS_PENDING_REVIEW` | Content submitted for review |
| `approved` | `STATUS_APPROVED` | Content approved for publication |
| `published` | `STATUS_PUBLISHED` | Live on the site |
| `rejected` | `STATUS_REJECTED` | Content rejected during review |
| `archived` | `STATUS_ARCHIVED` | Content moved to archive |

## State Machine

### Allowed Transitions

```
draft ──────────────► pending_review
draft ──────────────► archived

pending_review ─────► approved
pending_review ─────► rejected
pending_review ─────► draft

approved ───────────► published
approved ───────────► draft

published ──────────► draft
published ──────────► archived

rejected ───────────► draft
rejected ───────────► pending_review

archived ───────────► draft
```

### Key Rules

- Content can always go **back to draft** from any status except draft itself
- Once **published**, content cannot go back to `pending_review` or `approved` without first returning to `draft`
- **Archived** content can only return to `draft`

## Capability Requirements

Transitions are gated by WordPress-style capabilities:

| Transition | Required Capability |
|-----------|-------------------|
| `draft → pending_review` | `edit_posts` |
| `pending_review → approved` | `approve_posts` |
| `approved → published` | `publish_posts` |
| `draft → archived` | `delete_posts` |
| `published → archived` | `delete_posts` |
| All other transitions | `edit_posts` |

## Audit Trail

All status changes are logged in the `xp_workflow_log` table:

| Column | Description |
|--------|-------------|
| `post_id` | The content item being transitioned |
| `from_status` | Previous status |
| `to_status` | New status |
| `user_id` | Who performed the transition |
| `comment` | Review notes or reason |
| `created_at` | When the transition occurred |

## Admin UI

### Workflow Queue

Access the workflow queue at `/admin/workflow` to see all content pending review:

- **Pending Review** — Content submitted for approval
- **Approved** — Content approved but not yet published
- **Recently Published** — Recently published content

Each item shows:
- Content title and type
- Author name
- Current status
- Submitted/reviewed dates
- Actions (Approve, Reject, Publish, Send Back to Draft)

### Review Page

Clicking **"Review"** on a pending item shows:
- Side-by-side content view
- Review notes field
- Actions to Approve, Reject, or Send Back to Draft
- Workflow history log

### Bulk Actions

From the workflow queue, you can perform bulk operations:
- **Approve** selected items
- **Reject** selected items
- **Publish** selected approved items

## API

### Transition a Post

```php
use XooPress\Core\Workflow;

$workflow = $container->get('workflow');
$result = $workflow->transition($postId, 'pending_review', $userId, 'Ready for review');
```

Returns `true` on success or throws an exception if the transition is not allowed.

### Check Transition Validity

```php
$canTransition = $workflow->canTransition($currentStatus, $newStatus);
```

### Get Workflow History

```php
$history = $workflow->getHistory($postId);
```

Returns an array of all transitions for the post, ordered by creation date.