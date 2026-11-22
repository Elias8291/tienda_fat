import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../layouts/AdminLayout';
import { Eye, EyeOff, Trash } from '../../../components/Icons';

export default function Index({ users }) {
    const me = usePage().props.auth.user?.id;
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        username: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/usuarios', {
            preserveScroll: true,
            onSuccess: () => reset('name', 'username', 'password'),
        });
    };

    const remove = (user) => {
        if (confirm(`¿Eliminar a ${user.name}? Ya no podrá entrar al panel.`)) {
            router.delete(`/admin/usuarios/${user.id}`, { preserveScroll: true });
        }
    };

    return (
        <AdminLayout
            title="Usuarios"
            subtitle="Cuentas del personal que puede entrar al panel."
        >
            <div className="a-form">
                <form className="a-card" onSubmit={submit}>
                    <h2>Nuevo usuario</h2>
                    <label className={`a-field ${errors.name ? 'bad' : ''}`}>
                        <span>Nombre</span>
                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} maxLength={80} required />
                        {errors.name && <span className="a-error">{errors.name}</span>}
                    </label>
                    <label className={`a-field ${errors.username ? 'bad' : ''}`}>
                        <span>Usuario</span>
                        <input
                            type="text"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value)}
                            autoCapitalize="none"
                            autoCorrect="off"
                            spellCheck="false"
                            maxLength={40}
                            required
                        />
                        {errors.username && <span className="a-error">{errors.username}</span>}
                    </label>
                    <label className={`a-field ${errors.password ? 'bad' : ''}`}>
                        <span>Contraseña</span>
                        <span className="a-login-pass">
                            <input
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="new-password"
                                minLength={8}
                                required
                            />
                            <button type="button" onClick={() => setShowPassword((v) => !v)} aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}>
                                {showPassword ? <EyeOff /> : <Eye />}
                            </button>
                        </span>
                        {errors.password ? <span className="a-error">{errors.password}</span> : <em>Mínimo 8 caracteres.</em>}
                    </label>
                    <button className="a-btn" type="submit" disabled={processing}>
                        {processing ? 'Guardando…' : 'Crear usuario'}
                    </button>
                </form>

                <div className="a-card">
                    <h2>Personal</h2>
                    <div className="a-people">
                        {users.map((user) => (
                            <div className="a-person" key={user.id}>
                                <span className="a-avatar" aria-hidden="true">{(user.name || '?').slice(0, 1)}</span>
                                <div>
                                    <b>{user.name}</b>
                                    <span>@{user.username}</span>
                                </div>
                                {user.id === me ? (
                                    <span className="a-you">Tú</span>
                                ) : (
                                    <button className="a-icon del" onClick={() => remove(user)} aria-label={`Eliminar a ${user.name}`}>
                                        <Trash />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
