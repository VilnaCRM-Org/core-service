# Product Brief Distillate: Issue 29

Reject PATCH requests that contain no supported mutable fields. Return
`400 Bad Request` with a problem detail that explains the payload must contain
at least one supported field.
