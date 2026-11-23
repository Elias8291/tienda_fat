import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '../../../layouts/AdminLayout';
import { ChevronDown, ChevronUp, Eye, EyeOff, Pencil, Plus, Search, Trash } from '../../../components/Icons';
import { discount, money } from '../../../lib/money';

export default function Index({ products, filters, stats }) {
    const [q, setQ] = useState(filters.q ?? '');

    useEffect(() => {
        if (q === (filters.q ?? '')) return;
        const t = setTimeout(() => {
            router.get('/admin/productos', q ? { q } : {}, { preserveState: true, replace: true });
        }, 300);
        return () => clearTimeout(t);
    }, [q]);

    const move = (product, direction) =>
        router.patch(`/admin/productos/${product.id}/posicion`, { direction }, { preserveScroll: true });

    const toggle = (product) =>
        router.patch(`/admin/productos/${product.id}/visibilidad`, {}, { preserveScroll: true });

    const remove = (product) => {
        if (confirm(`¿Eliminar «${product.name}»? Esta acción no se puede deshacer.`)) {
            router.delete(`/admin/productos/${product.id}`, { preserveScroll: true });
        }
    };

    return (
        <AdminLayout
            title="Productos"
            subtitle={filters.q ? 'Resultados de la búsqueda.' : 'Este orden es el del catálogo. Usa las flechas para mover cada producto.'}
            actions={
                <Link href="/admin/productos/create" className="a-btn">
                    <Plus /> Nuevo producto
                </Link>
            }
        >
            <div className="a-toolbar">
                <div className="a-stats">
                    <div className="a-stat"><b>{stats.total}</b><span>Productos</span></div>
                    <div className="a-stat"><b>{stats.active}</b><span>Visibles</span></div>
                    <div className="a-stat"><b>{stats.total - stats.active}</b><span>Ocultos</span></div>
                </div>
                <div className="a-search">
                    <Search />
                    <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar por nombre o código" aria-label="Buscar por nombre o código" />
                </div>
            </div>

            {products.length === 0 ? (
                <div className="a-empty">
                    {filters.q ? 'No hay productos con ese nombre o código.' : 'Aún no hay productos. Crea el primero con «Nuevo producto».'}
                </div>
            ) : (
                <table className="a-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th style={{ textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {products.map((p, index) => (
                            <tr key={p.id}>
                                <td>
                                    <div className="a-prod">
                                        {p.images[0] ? (
                                            <img src={p.images[0].url} alt="" style={{ objectPosition: `${p.images[0].focus_x ?? 50}% ${p.images[0].focus_y ?? 50}%` }} />
                                        ) : <div className="a-noimg" />}
                                        <div>
                                            <b>{p.name}</b>
                                            <small>{p.code} · {p.images.length} {p.images.length === 1 ? 'foto' : 'fotos'}{p.description ? ` · ${p.description}` : ''}</small>
                                        </div>
                                    </div>
                                </td>
                                <td className="a-price">
                                    {discount(p) > 0 && <s>{money(p.old_price)}</s>}
                                    <b>{money(p.price)}</b>
                                    {discount(p) > 0 && <small>−{discount(p)}%</small>}
                                </td>
                                <td>
                                    <button
                                        className={`a-pill ${p.active ? 'on' : 'off'}`}
                                        onClick={() => toggle(p)}
                                        title={p.active ? 'Ocultar de la tienda' : 'Mostrar en la tienda'}
                                    >
                                        {p.active ? <Eye /> : <EyeOff />} {p.active ? 'Visible' : 'Oculto'}
                                    </button>
                                </td>
                                <td>
                                    <div className="a-actions">
                                        {!filters.q && (
                                            <>
                                                <button className="a-icon" onClick={() => move(p, 'up')} disabled={index === 0} aria-label="Subir">
                                                    <ChevronUp />
                                                </button>
                                                <button className="a-icon" onClick={() => move(p, 'down')} disabled={index === products.length - 1} aria-label="Bajar">
                                                    <ChevronDown />
                                                </button>
                                            </>
                                        )}
                                        <Link href={`/admin/productos/${p.id}/edit`} className="a-icon" aria-label="Editar">
                                            <Pencil />
                                        </Link>
                                        <button className="a-icon del" onClick={() => remove(p)} aria-label="Eliminar">
                                            <Trash />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </AdminLayout>
    );
}
