<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <div class="card-header" >
                         <router-link to="/" >
                        Example Component
                        </router-link>
                        </div>

                    <!-- <div class="card-body">
                        I'm an example component.
                    </div> -->
                    <div class="card-header" active-class="active">
                    User
                    </div>

                    <div class="card-body">
                        I'm an User component.
                    </div>
                    <button type="button" @click="logout()" class="btn btn-primary">Logout</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { API_BASE_URL } from '../const.js'
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
            logout() {
                var config = {
                    headers: { 'Accept': 'application/json',
                                'Authorization':'Bearer '+localStorage.getItem('token')},
                    };
                axios.post(API_BASE_URL + '/logout',"",config)
                    .then(response => {
                    //console.log(response);
                    
                    if(response.data!='')
                    {
                        const result=response.data;
                        // console.log(result.success);
                        if(result.success==1)
                        {
                            
                            localStorage.removeItem("userdata")
                            localStorage.removeItem("token")
                            this.$toast.success({
                                title:'Success',
                                message:result.data.message
                            })
                            this.$router.push('/');
                            
                        }
                        else
                        {
                            
                            this.$toast.error({
                                title:'Error',
                                message:result.error[0]
                            })
                        }
                    }
                    
                    }).catch(err => {
                     console.log(err)
                })
            }
        }
    }
</script>
