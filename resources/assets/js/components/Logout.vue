<template>
    <div>
        <button type="submit" @click="logout()" class="btn btn-primary btn-block btn-lg">Logout</button>                  
        <button type="button" @click="checkEmit()" class="btn btn-primary btn-block btn-lg">Change Name</button>                  
    </div>
</template>

<script>
import { API_BASE_URL } from '../const.js'
    export default {
        props:['token'],
        data() {
             return {    }
        },
        mounted() {
            console.log('Component mounted.')
            // let data=JSON.parse(localStorage.getItem('userdata'));
            // this.name=data.user.name;
            // this.email=data.user.email;
        },
        methods: {
            logout() {
                var config = {
                    headers: { 'Accept': 'application/json',
                                'Authorization':'Bearer '+this.token},
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
            },
            checkEmit()
            {
                this.$emit('displayName','Tony');
            }
        }
    }
</script>
