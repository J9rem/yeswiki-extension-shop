/*
 * This file is part of the YesWiki Extension shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : hpf-helloasso-payments-table
 */

import asyncHelper from '../../alternativeupdatej9rem/javascripts/asyncHelper.js'
import SpinnerLoader from '../../bazar/presentation/javascripts/components/SpinnerLoader.js'

const rootsElements = ['.helloasso-direct-payment-form > .app'];
const isVueJS3 = (typeof window.Vue.createApp == "function");

let appParams = {
    components: {SpinnerLoader},
    data(){
        return {
            args: {},
            error: false,
            invalidPayersKeys: [],
            isLoading: true,
            payer: {
                address: '',
                birthDate: '',
                city: '',
                country: 'FRANCE',
                email: '',
                firstName: '',
                lastName: '',
                zipCode: ''
            },
            refreshing: false,
            token: ''
        }
    },
    computed:{
        element(){
            return this.getElement()
        },
    },
    methods:{
        checkValidity(){
            this.element.querySelectorAll('input').forEach((element)=>{
                element.checkValidity()
            })
        },
        getElement(){
            return isVueJS3 ? this.$el.parentNode : this.$el
        },
        importArgs(){
            [
                'address',
                'city',
                'email',
                'firstName',
                'lastName',
                'zipCode'
            ].forEach((key)=>{
                this.$set(this.payer,key,this.args?.[key] ?? '')
            })
        },
        pay(){
            if (!(this.invalidPayersKeys?.length > 0)){
                this.refreshing = true
                asyncHelper.fetch(
                    window.wiki.url('?api/shop/helloasso/directpayment/getformurl'),
                    'post',
                    {
                        payer:this.payer,
                        token: this.token,
                        itemName: this.args?.itemName ?? '',
                        totalAmount: this.args?.totalAmount ?? '',
                        meta: this.args?.meta ?? [],
                        meta: this.args?.meta ?? [],
                        backUrl: this.args?.['shop backUrl'] ?? '',
                        errorUrl: this.args?.['shop errorUrl'] ?? '',
                        returnUrl: this.args?.['shop returnUrl'] ?? '',
                        containsDonation: this.args?.containsDonation ?? false
                    }
                )
                .finally(()=>{
                    this.refreshing = false
                })
                .then((data)=>{
                    if (!(data?.redirectUrl?.length > 0)){
                        throw new Error(`data does not have redirect url : ${JSON.stringify(data)}`);
                    }
                    window.location = data.redirectUrl
                })
                .catch((error)=>{
                    this.error = true
                    asyncHelper.manageError(error)
                    if (this.args?.['shop errorUrl'].length > 0){
                        // window.location = this.args['shop errorUrl']
                    }
                })
            }
        },
        toggleValidity(name, status){
            if (status && !this.invalidPayersKeys.includes(name)){
                this.invalidPayersKeys.push(name)
            } else if (!status && this.invalidPayersKeys.includes(name)) {
                const idx = this.invalidPayersKeys.indexOf(name)
                this.invalidPayersKeys.splice(idx,1)
            }
        },
        updateValidityOfData(newPayerData = null){
            const newData = (newPayerData === null) ? this.payer : newPayerData
            Object.keys(this.payer).forEach((k)=>{
                this.toggleValidity(k, !(newData?.[k]?.length > 0))
            })
            this.checkValidity()
            if (!this.invalidPayersKeys.includes('birthDate')){
                try {
                    const birthDate = this.payer.birthDate
                    const birthDateExploded = birthDate.match(/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/)
                    if (birthDateExploded === null
                        || !(birthDateExploded?.length > 0)){
                        throw new Error('not a date')
                    }
                    const calculatedDate = new Date(birthDateExploded[3],birthDateExploded[2]-1,birthDateExploded[1])
                    if (calculatedDate === null
                        || calculatedDate?.toString() === 'Invalid Date'
                        || calculatedDate.getFullYear() != birthDateExploded[3]
                        || calculatedDate.getMonth() != (birthDateExploded[2] - 1)
                        || calculatedDate.getDate() != birthDateExploded[1]
                        ){
                        throw new Error('not a date')
                    }
                } catch (error) {
                    this.toggleValidity('birthDate', true)
                }
            }
        }
    },
    mounted(){
        $(this.element).on('dblclick',function(e) {
            return false
        })
        this.element.style.display = 'initial';
        const rawArgs = this.element?.dataset?.args
        if (rawArgs){
            try {
                this.args = JSON.parse(rawArgs)
                this.importArgs()
                this.token = this.element?.dataset?.token ?? ''
            } catch (error) {
                console.error(error)
            }
        }
        this.isLoading = false
    },
    watch:{
        payer:{
            deep: true,
            handler(newPayerData) {
                this.updateValidityOfData(newPayerData)
            }
        },
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (isVueJS3){
        let app = window.Vue.createApp(appParams)
        app.config.globalProperties.wiki = window.wiki
        app.config.globalProperties._t = window._t
        rootsElements.forEach(elem => {
            app.mount(elem)
        })
    } else {
        window.Vue.prototype.wiki = window.wiki
        window.Vue.prototype._t = _t;
        rootsElements.forEach(elem => {
            new Vue({
                ...{el:elem},
                ...appParams
            })
        })
    }
})