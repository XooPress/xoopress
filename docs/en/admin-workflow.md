# Content Workflow (Admin)

The Content Workflow system provides a review and approval queue for managing content before publication.

## Accessing the Workflow

Navigate to **Admin → Workflow** (`/admin/workflow`) to access the content review queue.

## Workflow Queue

The workflow page displays content organized by status:

### Pending Review
Content that has been submitted for approval. Each item shows:
- **Title** — Click to view the full content
- **Author** — Who created/submitted the content
- **Type** — Post, page, or custom content type
- **Submitted** — When it was submitted for review
- **Actions** — Approve, Reject, or Send Back to Draft

### Approved
Content that has been approved but not yet published:
- **Title** — Click to view
- **Reviewer** — Who approved it
- **Approved** — When it was approved
- **Actions** — Publish or Send Back to Draft

### Recently Published
Content published through the workflow:
- **Title** — Click to view
- **Published by** — Who published it
- **Published** — When it was published

## Reviewing Content

Click **"Review"** on any pending item to open the review page:

1. **Content View** — See the full title, content, excerpt, and metadata
2. **Review Notes** — Add comments or feedback for the author
3. **Actions:**
   - **Approve** — Move to approved status (ready for publication)
   - **Reject** — Send back to draft with feedback
   - **Send Back to Draft** — Return to draft status without approval

## Bulk Actions

Select multiple items using checkboxes and perform bulk operations:
- **Approve Selected** — Approve all selected pending items
- **Reject Selected** — Reject all selected items
- **Publish Selected** — Publish all selected approved items

## Workflow History

Each post has a complete workflow history showing:
- Status transitions (draft → pending_review → approved → published)
- Who performed each transition
- Timestamps for each change
- Review comments

Access the history from the post edit page or the workflow queue.

## Status Overview

| Status | Meaning |
|--------|---------|
| **Draft** | Initial state, content being created |
| **Pending Review** | Submitted for editorial review |
| **Approved** | Approved by editor, ready to publish |
| **Published** | Live on the website |
| **Rejected** | Sent back by reviewer |
| **Archived** | Archived (not visible on the site) |