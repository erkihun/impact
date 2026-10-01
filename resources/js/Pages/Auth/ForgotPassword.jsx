import { Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { Editor, Action, field, useWorkspace } from '../../Components/Workspace/UI';
export default function ForgotPassword(){return <AuthLayout title="Forgot your password?"><Editor action="/forgot-password" initial={{email:''}} fields={[field('email','Email','email',{required:true,autoComplete:'email'})]} submit="Email Password Reset Link" /></AuthLayout>;}