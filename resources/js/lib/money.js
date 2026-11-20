export const money = (value) => '$' + Number(value).toFixed(2);

export const discount = (product) => {
    const price = Number(product.price);
    const original = Number(product.old_price);
    if (!original || original <= price) return 0;
    return Math.round((1 - price / original) * 100);
};
