import { Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { Editor, field, useWorkspace } from '../../Components/Workspace/UI';

export default function Login() {
    const { t } = useWorkspace();
    return <AuthLayout title="Sign in" lede="Use the email address your administrator invited." step={1}>
        <Editor action="/login" initial={{ email: '', password: '', remember: false }} fields={[
            field('email', 'Email address', 'email', { required: true, autoComplete: 'username', id: 'email' }),
            field('password', 'Password', 'password', { required: true, autoComplete: 'current-password', id: 'password' }),
            field('remember', 'Keep me signed in on this device', 'checkbox'),
        ]} submit="Sign in">
            <Link className="text-link text-sm" href="/forgot-password">{t('Forgot your password?')}</Link>
        </Editor>
    </AuthLayout>;
}
