import '../../../css/admin.css';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Eye, EyeOff } from '../../components/Icons';

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/login', { onFinish: () => setData('password', '') });
    };

    const error = errors.username || errors.password;

    return (
        <div className="a-login">
            <Head title="Acceso del personal" />

            <header className="a-login-bar">
                <a href="/">← Tienda</a>
                <span className="a-login-logo">Fati Nails</span>
                <span />
            </header>

            <form className="a-login-form" onSubmit={submit}>
                <h1>Acceso del personal</h1>
                <p>Solo para el equipo administrativo.</p>

                <label className="a-login-field">
                    <span>Usuario</span>
                    <input
                        type="text"
                        value={data.username}
                        onChange={(e) => setData('username', e.target.value)}
                        className={errors.username ? 'bad' : ''}
                        autoComplete="username"
                        autoCapitalize="none"
                        autoCorrect="off"
                        spellCheck={false}
                        autoFocus
                        required
                    />
                </label>

                <label className="a-login-field">
                    <span>Contraseña</span>
                    <div className="a-login-pass">
                        <input
                            type={showPassword ? 'text' : 'password'}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className={errors.password ? 'bad' : ''}
                            autoComplete="current-password"
                            required
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((v) => !v)}
                            aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                        >
                            {showPassword ? <EyeOff /> : <Eye />}
                        </button>
                    </div>
                </label>

                {error && <p className="a-login-error" role="alert">{error}</p>}

                <label className="a-check a-login-remember">
                    <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    Mantener la sesión iniciada
                </label>

                <button className="a-login-submit" type="submit" disabled={processing}>
                    {processing ? 'Entrando…' : 'Entrar'}
                </button>
            </form>
        </div>
    );
}
