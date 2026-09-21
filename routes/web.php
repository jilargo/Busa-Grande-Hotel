<?php

declare(strict_types=1);

/*
 * Route table.
 *
 * Each entry: [METHOD, PATH, "Controller@method", [middleware...]]
 *
 * Middleware:
 *   - guest          only for logged-OUT visitors (login/register pages)
 *   - auth           any signed-in user
 *   - role:admin     admin only
 *   - role:staff     staff only
 *   - role:guest     guest only
 *   - role:staff,admin   staff or admin
 *
 * Every POST/PUT/PATCH/DELETE request is CSRF-checked by the router.
 */

return [
];
