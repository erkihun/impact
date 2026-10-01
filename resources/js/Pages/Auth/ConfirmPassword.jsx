import { Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { Editor, Action, field, useWorkspace } from '../../Components/Workspace/UI';
export default function ConfirmPassword(){return <AuthLayout title="Confirm Password"><Editor action="/confirm-password" initial={{password:''}} fields={[field('password','Password','password',{required:true,autoComplete:'current-password'})]} submit="Confirm" /></AuthLayout>;}