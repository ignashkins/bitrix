import {Vue} from 'ui.vue';
import {VuexBuilderModel} from 'ui.vue.vuex';
import {Type} from 'main.core';
import {Loader as LoaderConst} from 'sale.checkout.const';

export class Basket extends VuexBuilderModel
{
    getName()
    {
        return 'basket';
    }

    getState()
    {
        return {
            basket: [],
            status: LoaderConst.status.none,
            currency: null,
            discount: Basket.getDiscountItem(),
            total: Basket.getTotalItem(),
            errors: []
        }
    }

    getBaseItem()
    {
        return {
            id: 0,
            name: null,
            quantity: 0,
            measureText: null,
            currency: null,
            module: null,
            productProviderClass: null,
            sum: 0.0,           // finalSum,    basket sum with discounts and taxes => basketItem->getPrice() * basketItem->getQuantity()
            price: 0.0,         // finalPrice,  basket price with discounts and taxes => basketItem->getPrice()
            baseSum: 0.0,       // baseSum,     basket sum without discounts and taxes => basketItem->getBasePrice() * basketItem->getQuantity()
            basePrice: 0.0,     // basePrice,   basket price without discounts and taxes => basketItem->getBasePrice()
            discount: Basket.getDiscountItem(),
            props: [],
            product: this.getProductItem(),
            deleted: "N",
            status: LoaderConst.status.none,
        };
    }

    static getPropsItem()
    {
        return {
            code: "",
            id: 0,
            value: "",
            sort: 0,
            name: ""
        };
    }

    static getDiscountItem()
    {
        return {
            sum: 0,  // => (basketItem->getBasePrice() * basketItem->getQuantity()) - (basketItem->getPrice() * basketItem->getQuantity())
            price: 0 // => basketItem->getDiscountPrice();
        };
    }

    static getDiscountTotalItem()
    {
        return {
            sum: 0, // => order->getDiscountPrice() + (basket->getBasePrice() - basket->getPrice())
        };
    }

    static getTotalItem()
    {
        return {
            price: 0.0,     //finalPrice, basket price with discounts and taxes => basket->getPrice()
            basePrice: 0.0, //basePrice,  basket price without discounts => basket->getBasePrice();
        };
    }

    getProductItem()
    {
        return {
            id: 0,
            picture : this.getVariable('product.noImage', null),
            detailPageUrl : "",
            availableQuantity: 0,
            ratio: 0,
        };
    }
    
    static isFloat(value)
    {
        return parseInt(value) !== parseFloat(value);
    }
    
    validate(fields)
    {
        const result = {};

        if (Type.isObject(fields.basket))
        {
            result.basket = this.validateBasket(fields.basket);
        }

        if (Type.isString(fields.status))
        {
            result.status = fields.status.toString()
        }

        if (Type.isString(fields.currency))
        {
            result.currency = fields.currency.toString();
        }

        if (Type.isObject(fields.discount))
        {
            result.discount = this.validateTotalDiscount(fields.discount);
        }

        if (Type.isObject(fields.total))
        {
            result.total = this.validateTotal(fields.total);
        }

        return result;
    }

    validateBasket(fields)
    {
        const result = {};

        if (Type.isString(fields.status))
        {
            const allowed = Object.values(LoaderConst.status);

            let status = fields.status.toString();

            result.status = allowed.includes(status) ? status : LoaderConst.status.none;
        }

        if (Type.isString(fields.deleted))
        {
            result.deleted = fields.deleted.toString() === 'Y' ? 'Y':'N';
        }

        if (Type.isNumber(fields.id) || Type.isString(fields.id))
        {
            result.id = parseInt(fields.id);
        }

        if (Type.isString(fields.name))
        {
            result.name = fields.name.toString();
        }

        if (Type.isNumber(fields.quantity) || Type.isString(fields.quantity))
        {
            result.quantity = parseFloat(fields.quantity);
        }

        if (Type.isString(fields.measureText))
        {
            result.measureText = fields.measureText.toString();
        }

        if (Type.isNumber(fields.sum) || Type.isString(fields.sum))
        {
            result.sum = parseFloat(fields.sum);
        }

        if (Type.isNumber(fields.price) || Type.isString(fields.price))
        {
            result.price = parseFloat(fields.price);
        }

        if (Type.isNumber(fields.baseSum) || Type.isString(fields.baseSum))
        {
            result.baseSum = parseFloat(fields.baseSum);
        }

        if (Type.isNumber(fields.basePrice) || Type.isString(fields.basePrice))
        {
            result.basePrice = parseFloat(fields.basePrice);
        }

        if (Type.isString(fields.currency))
        {
            result.currency = fields.currency.toString();
        }

        if (Type.isString(fields.module))
        {
            result.module = fields.module.toString();
        }

        if (Type.isString(fields.productProviderClass))
        {
            result.productProviderClass = fields.productProviderClass.toString();
        }

        if (Type.isObject(fields.product))
        {
            result.product = this.validateProduct(fields.product);
        }
        
        if (Type.isObject(fields.props))
        {
            result.props = [];
            fields.props.forEach((item)=>{
                let fields = this.validateProps(item);
                result.props.push(fields);
            })
        }

        if (Type.isObject(fields.discount))
        {
            result.discount = this.validateDiscount(fields.discount);
        }

        return result;
    }

    validateDiscount(fields)
    {
        const result = {};

        if (Type.isNumber(fields.sum) || Type.isString(fields.sum))
        {
            result.sum = parseFloat(fields.sum);
        }

        if (Type.isNumber(fields.price) || Type.isString(fields.price))
        {
            result.price = parseFloat(fields.price);
        }

        return result;
    }

    validateTotalDiscount(fields)
    {
        const result = {};

        if (Type.isNumber(fields.sum) || Type.isString(fields.sum))
        {
            result.sum = parseFloat(fields.sum);
        }

        return result;
    }

    validateTotal(fields)
    {
        const result = {};

        if (Type.isNumber(fields.price) || Type.isString(fields.price))
        {
            result.price = parseFloat(fields.price);
        }

        if (Type.isNumber(fields.basePrice) || Type.isString(fields.basePrice))
        {
            result.basePrice = parseFloat(fields.basePrice);
        }

        return result;
    }

    validateProduct(fields)
    {
        const result = {};
        
        try
        {
            for (let field in fields)
            {
                if (!fields.hasOwnProperty(field))
                {
                    continue;
                }

                if (field === 'id')
                {
                    if (Type.isNumber(fields.id) || Type.isString(fields.id))
                    {
                        result[field] = fields.id;
                    }
                }
                else if (field === 'picture')
                {
                    if (Type.isString(fields.picture) && fields.picture.length > 0)
                    {
                        result[field] = fields.picture.toString();
                    }
                }
                else if (field === 'detailPageUrl')
                {
                    if (Type.isString(fields.detailPageUrl))
                    {
                        result[field] = fields.detailPageUrl.toString();
                    }
                }
                else if (field === 'availableQuantity')
                {
                    if (Type.isNumber(fields.availableQuantity) || Type.isString(fields.availableQuantity))
                    {
                        result.availableQuantity = parseFloat(fields.availableQuantity)
                    }
                }
                else if (field === 'ratio')
                {
                    if (Type.isNumber(fields.ratio) || Type.isString(fields.ratio))
                    {
                        result.ratio = parseFloat(fields.ratio)
                    }
                }
                else
                {
                    result[field] = fields[field];
                }
            }
        }
        catch (e) {}

        return result;
    }

    validateProps(fields)
    {
        const result = {};

        if (Type.isNumber(fields.id) || Type.isString(fields.id))
        {
            result.id = parseInt(fields.id);
        }

        if (Type.isString(fields.name))
        {
            result.name = fields.name.toString();
        }

        if (Type.isString(fields.code))
        {
            result.code = fields.code.toString();
        }

        if (Type.isString(fields.value))
        {
            result.value = fields.value.toString();
        }

        if (Type.isNumber(fields.sort) || Type.isString(fields.sort))
        {
            result.sort = parseInt(fields.sort);
        }
        
        return result;
    }

    getActions()
    {
        return {
            setTradingPlatformId: ({ commit }, payload) =>
            {
                payload = this.validate(payload);
                commit('setTradingPlatformId', payload);
            },
            setStatus: ({ commit }, payload) =>
            {
                payload = this.validate(payload);

                const status = [
                    LoaderConst.status.none,
                    LoaderConst.status.wait,
                ];

                payload.status = status.includes(payload.status) ? payload.status : LoaderConst.status.none;
                commit('setStatus', payload);
            },
            addItem: ({ commit }, payload) =>
            {
                payload.fields = this.validateBasket(payload.fields);
                commit('addItem', payload);
            },
            changeItem: ({ commit }, payload) =>
            {
                payload.fields = this.validateBasket(payload.fields);
                commit('updateItem', payload);
            },
            removeItem({ commit }, payload)
            {
                commit('deleteItem', payload);
            },
            setFUserId: ({ commit }, payload) =>
            {
                payload = this.validate(payload);
                commit('setFUserId', payload);
            },
            setCurrency: ({ commit }, payload) =>
            {
                payload = this.validate(payload);
                commit('setCurrency', payload);
            },
            setDiscount: ({ commit }, payload) =>
            {
                payload = this.validateDiscount(payload);
                commit('setDiscount', payload);
            },
            setTotal: ({ commit }, payload) =>
            {
                payload = this.validateTotal(payload);
                commit('setTotal', payload);
            }
        }
    }

    getGetters()
    {
        return {

            getStatus: state =>
            {
                return state.status;
            },
            get: state => id =>
            {
                if (!state.basket[id] || state.basket[id].length <= 0)
                {
                    return [];
                }

                return state.basket[id];
            },
            getBasket: state =>
            {
                return state.basket;
            },
            getBaseItem: state =>
            {
                return this.getBaseItem();
            },
            getCurrency: state =>
            {
                return state.currency;
            },
            getDiscount: state =>
            {
                return state.discount;
            },
            getTotal: state =>
            {
                return state.total;
            }
        }
    }

    getMutations()
    {
        return {
            setStatus: (state, payload) =>
            {
                let item = { status: LoaderConst.status.none };

                item = Object.assign(item, payload);
                state.status = item.status;
            },
            setCurrency: (state, payload) =>
            {
                let item = { currency: null };

                item = Object.assign(item, payload);
                state.currency = item.currency;
            },
            setDiscount: (state, payload) =>
            {
                let item = Basket.getDiscountTotalItem();
                item = Object.assign(item, payload);
                state.discount = Object.assign(item, payload);
            },
            setTotal: (state, payload) =>
            {
                let item = Basket.getTotalItem();
                item = Object.assign(item, payload);
                state.total = Object.assign(item, payload);
            },
            addItem: (state, payload) =>
            {
                let item = this.getBaseItem();

                item = Object.assign(item, payload.fields);
    
                if (Type.isObject(payload.fields.product))
                {
                    item.product = Object.assign(
                        item.product,
                        payload.fields.product
                    )
                }
                
                if (Type.isObject(item.props))
                {
                    item.props.forEach((fields, index)=>{
                        let prop = Basket.getPropsItem();
                        prop = Object.assign(prop, fields);

                        item.props[index] = prop;
                    })
                }

                state.basket.push(item);
                state.basket.forEach((item, index) => {
                    item.sort = index + 1;
                });
            },
            updateItem: (state, payload) =>
            {
                if (typeof state.basket[payload.index] === 'undefined')
                {
                    Vue.set(state.basket, payload.index, this.getBaseItem());
                }
    
                if (Type.isObject(payload.fields.product))
                {
                    payload.fields.product = Object.assign(
                        state.basket[payload.index].product,
                        payload.fields.product
                    )
                }
                
                if (Type.isObject(payload.fields.props))
                {
                    payload.fields.props.forEach((fields, index)=>{
                        let item = Basket.getPropsItem();
                        item = Object.assign(item, fields);

                        payload.fields.props[index] = item;
                    })
                }
    
                state.basket[payload.index] = Object.assign(
                    state.basket[payload.index],
                    payload.fields
                );
            },
            deleteItem: (state, payload) =>
            {
                // delete state.basket[payload.index];
                state.basket.splice(payload.index, 1);
            },
            clearBasket: (state) =>
            {
                state.basket = [];
            },
            clearDiscount: (state) =>
            {
                state.discount = Basket.getDiscountItem();
            },
            clearTotal: (state) =>
            {
                state.total = Basket.getTotalItem();
            },
            setErrors: (state, payload) =>
            {
                state.errors = payload;
            },
            clearErrors: (state) =>
            {
                state.errors = [];
            }
        }
    }
}