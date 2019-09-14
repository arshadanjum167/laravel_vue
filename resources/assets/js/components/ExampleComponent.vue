<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <!-- <div class="card-header" active-class="active">Example Component</div> -->

                    
                    <div class="card-header" >
                    <router-link to="/user" >
                        <div class="text-center">
                        <h4> Login </h4>
                        </div>
                    </router-link>
                    </div>

                    <div class="card-body">
                        Login to your Account

                        <form autocomplete="off" @submit.prevent="login" method="post">
                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" class="form-control" placeholder="user@example.com" v-model="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">password</label>
                                <input type="password" id="password" class="form-control" v-model="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
                        </form>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
</template>
<style src="cxlt-vue2-toastr/dist/css/cxlt-vue2-toastr.css"></style>
<script>
// import { Observable } from 'rxjs';

import { API_BASE_URL } from '../const.js'
    export default {
         data() {
             
            return {
                email: null,
                password: null,
                has_error: false,
            }
        },
        mounted() {
            console.log('Login Component mounted.')
            const token = localStorage.getItem('token');
            
            if (token) 
            {
                this.$router.push('user');
            }
        },

        // subscriptions:{
        //      coundDown: 1
        // },

        methods: {
        login() {
            
            try 
            {
                //let data={"email":this.email,"password":this.password};
                let data=new FormData();
                data.append('email',this.email);
                data.append('password',this.password);
                var config = {
                    headers: { 'Accept': 'application/json',
                                'Authorization':'Bearer '+localStorage.getItem('token')},
                    };
                axios.post(API_BASE_URL + '/login',data,config)
                    .then(response => {
                    //console.log(response);
                    
                    if(response.data!='')
                    {
                        const result=response.data;
                         console.log(result.data);
                        if(result.success==1)
                        {
                            localStorage.setItem('userdata', JSON.stringify(result.data))
                            this.$toast.success({
                                title:'Success',
                                message:result.data.message
                            })
                            this.$router.push('user');
                            
                        }
                        else
                        {
                            this.has_error=true;
                            this.$toast.error({
                                title:'Error',
                                message:result.error[0]
                            })
                        }
                    }else{
                        this.has_error=true;
                    }
                    
                    }).catch(err => {
                     console.log(err)
                })
            } catch (e) {
                // handle the authentication error here
                
                console.log('Error : '+e);
            }
        }
        }
    }
</script>
