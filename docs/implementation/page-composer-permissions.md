# Page composer permissions

| Permission | Capability |
|---|---|
| `pages.view` | list and inspect compositions |
| `pages.create` | create/clone editable drafts |
| `pages.update` | section add, version save, order, duplicate, archive/restore, submit |
| `pages.preview` | use signed non-public preview |
| `pages.approve` | approve or request changes |
| `pages.publish` | schedule, publish, archive |
| `pages.delete` | archive removable sections/compositions |
| `navigation.manage` | publish localized primary/footer label, visibility, and order updates |

Role defaults:

- Contributor: page visibility only.
- Editor: create, update, preview.
- Reviewer: view, preview, approve/request changes.
- Publisher: view, preview, publish and media approval.
- Administrator: all page and navigation controls.
- Super administrator: receives the complete catalog through the existing role seeder.

Backend middleware and policies enforce every route. The UI is permission-aware but is not the authorization boundary. Direct URL requests without the permission return 403. Published versions also reject mutation even for otherwise authorized editors.
