import { label } from './UI';

const moduleNames = { pages: 'Website pages', navigation: 'Menus and footer', 'case-studies': 'Case studies', seo: 'SEO and Social Sharing', audit: 'Audit log', privacy: 'Privacy and Retention' };
export const moduleLabel = code => moduleNames[code] ?? label(code);
export const permissionLabel = permission => permission.description || label(permission.code);
export function groupPermissions(permissions) {
    return permissions.reduce((groups, permission) => {
        const module = permission.code.split('.')[0];
        (groups[module] ??= []).push(permission);
        return groups;
    }, {});
}
export function matchesPermission(permission, query, t) {
    return `${permission.code} ${permissionLabel(permission)} ${t(permissionLabel(permission))} ${t(moduleLabel(permission.code.split('.')[0]))}`.toLocaleLowerCase().includes(query.trim().toLocaleLowerCase());
}
