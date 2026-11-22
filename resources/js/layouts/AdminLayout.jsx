import '../../css/admin.css';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bag, Logout, Store, Users } from '../components/Icons';

export default function AdminLayout({ title, subtitle, actions, children }) {
    const { auth, flash } = usePage().props;
    const logout = () => router.post('/admin/logout');

    return (
        <div className="a-shell">
            <Head title={`${title} · Administración`} />
            <aside className="a-side">
                <div className="a-brand">
                    Fati Nails
                    <small>Administración</small>
                </div>
                <nav className="a-nav">
                    <Link href="/admin/productos" className={usePage().url.startsWith('/admin/productos') ? 'on' : ''}>
                        <Bag /> <span>Productos</span>
                    </Link>
                    <Link href="/admin/usuarios" className={usePage().url.startsWith('/admin/usuarios') ? 'on' : ''}>
                        <Users /> <span>Usuarios</span>
                    </Link>
                    <a href="/" target="_blank" rel="noreferrer">
                        <Store /> <span>Ver tienda</span>
                    </a>
                    <button className="a-mobile-out" onClick={logout} aria-label="Cerrar sesión">
                        <Logout />
                    </button>
                </nav>
                <div className="a-me">
                    <span className="a-avatar" aria-hidden="true">{(auth.user?.name || '?').slice(0, 1)}</span>
                    <b>{auth.user?.name}</b>
                    <span>@{auth.user?.username}</span>
                    <button onClick={logout}><Logout /> Cerrar sesión</button>
                </div>
            </aside>

            <main className="a-main">
                <div className="a-top">
                    <div>
                        <h1>{title}</h1>
                        {subtitle && <p>{subtitle}</p>}
                    </div>
                    {actions}
                </div>
                {flash?.success && <div className="a-flash">{flash.success}</div>}
                {children}
            </main>
        </div>
    );
}
