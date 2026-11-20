import { Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AdminLayout from '../../../layouts/AdminLayout';
import { Close, Upload } from '../../../components/Icons';
import { discount, money } from '../../../lib/money';

function offer(original, percent) {
    const base = Number(original);
    const pct = percent === '' ? 0 : Number(percent);
    if (!Number.isFinite(base) || base < 0 || !Number.isFinite(pct) || pct < 0 || pct >= 100) return null;
    const price = pct === 0 ? base : Math.round(base * (100 - pct)) / 100;
    return { price, old: pct > 0 && price < base ? base : '', pct: price < base ? pct : 0 };
}

function Field({ label, error, hint, children }) {
    return (
        <label className={`a-field ${error ? 'bad' : ''}`}>
            <span>{label}</span>
            {children}
            {hint && !error && <em>{hint}</em>}
            {error && <span className="a-error">{error}</span>}
        </label>
    );
}

export default function Form({ product }) {
    const editing = Boolean(product);
    const { data, setData, post, processing, errors } = useForm({
        name: product?.name ?? '',
        description: product?.description ?? '',
        price: product?.price ?? '',
        old_price: product?.old_price ?? '',
        active: product?.active ?? true,
        images: [],
        remove_images: [],
        ...(editing ? { _method: 'put' } : {}),
    });

    const [original, setOriginal] = useState(product?.old_price || product?.price || '');
    const [percent, setPercent] = useState(() => {
        const current = product ? discount(product) : 0;
        return current > 0 ? String(current) : '';
    });
    const deal = offer(original, percent);
    const previews = useMemo(() => data.images.map((file) => URL.createObjectURL(file)), [data.images]);
    useEffect(() => () => previews.forEach((url) => URL.revokeObjectURL(url)), [previews]);

    const imageErrors = Object.entries(errors)
        .filter(([key]) => key === 'images' || key.startsWith('images.'))
        .map(([, message]) => message);

    const changeOffer = (nextOriginal, nextPercent) => {
        setOriginal(nextOriginal);
        setPercent(nextPercent);
        const next = offer(nextOriginal, nextPercent);
        if (next) setData({ ...data, price: next.price, old_price: next.old });
    };

    const addFiles = (e) => {
        setData('images', [...data.images, ...Array.from(e.target.files)]);
        e.target.value = '';
    };

    const dropNew = (index) => setData('images', data.images.filter((_, i) => i !== index));

    const toggleExisting = (id) =>
        setData(
            'remove_images',
            data.remove_images.includes(id) ? data.remove_images.filter((i) => i !== id) : [...data.remove_images, id],
        );

    const submit = (e) => {
        e.preventDefault();
        post(editing ? `/admin/productos/${product.id}` : '/admin/productos', { forceFormData: true });
    };

    return (
        <AdminLayout
            title={editing ? 'Editar producto' : 'Nuevo producto'}
            subtitle={editing ? product.name : 'Completa los datos y sube al menos una foto.'}
        >
            <form className="a-form" onSubmit={submit}>
                <div className="a-card">
                    <h2>Información</h2>
                    <Field label="Nombre" error={errors.name}>
                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} maxLength={120} required />
                    </Field>
                    <Field label="Descripción" error={errors.description} hint="Opcional. Se muestra al abrir el producto.">
                        <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} maxLength={1000} />
                    </Field>
                    <div className="a-two">
                        <Field label="Precio" error={errors.price}>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                value={original}
                                onChange={(e) => changeOffer(e.target.value, percent)}
                                required
                            />
                        </Field>
                        <Field label="Descuento %" error={errors.old_price} hint="Opcional. Se descuenta de este precio.">
                            <input
                                type="number"
                                step="1"
                                min="1"
                                max="99"
                                value={percent}
                                placeholder="Sin descuento"
                                onChange={(e) => changeOffer(original, e.target.value)}
                            />
                        </Field>
                    </div>
                    {deal && (
                        <div className="a-off">
                            {deal.pct > 0 && <b>−{deal.pct}%</b>}
                            <span>{deal.pct > 0 ? `Precio original ${money(deal.old)}` : 'Sin descuento'}</span>
                            <span>El precio queda en {money(deal.price)}</span>
                        </div>
                    )}
                    <label className="a-check">
                        <input type="checkbox" checked={data.active} onChange={(e) => setData('active', e.target.checked)} />
                        Visible en la tienda
                    </label>
                </div>

                <div className="a-card">
                    <h2>Fotos</h2>
                    <label className="a-drop">
                        <Upload />
                        Subir fotos
                        <small>JPG, PNG o WEBP · máximo 4 MB cada una</small>
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple onChange={addFiles} />
                    </label>
                    {imageErrors.map((message) => (
                        <span key={message} className="a-error">{message}</span>
                    ))}

                    {(product?.images.length > 0 || previews.length > 0) && (
                        <div className="a-pics">
                            {product?.images.map((img, i) => {
                                const gone = data.remove_images.includes(img.id);
                                return (
                                    <div key={img.id} className={`a-pic ${gone ? 'gone' : ''}`}>
                                        <img src={img.url} alt="" />
                                        <button type="button" onClick={() => toggleExisting(img.id)} aria-label={gone ? 'Conservar foto' : 'Quitar foto'}>
                                            {gone ? '↺' : <Close />}
                                        </button>
                                        {gone ? <span className="a-tag">Se quitará</span> : i === 0 && <span className="a-tag">Portada</span>}
                                    </div>
                                );
                            })}
                            {previews.map((url, i) => (
                                <div key={url} className="a-pic">
                                    <img src={url} alt="" />
                                    <button type="button" onClick={() => dropNew(i)} aria-label="Quitar foto"><Close /></button>
                                    <span className="a-tag">Nueva</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="a-foot" style={{ gridColumn: '1 / -1' }}>
                    <Link href="/admin/productos" className="a-btn ghost">Cancelar</Link>
                    <button className="a-btn" type="submit" disabled={processing}>
                        {processing ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear producto'}
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
