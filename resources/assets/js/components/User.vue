<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-default">
                    <div class="card-header" >
                         <router-link to="/" >
                        User Detail
                        </router-link>
                        </div>
                        <p>
                            Count : {{ count }}
                        </p>
                    <!-- <div class="card-body">
                        I'm an example component.
                    </div> -->
                    <!-- <div class="card-header" active-class="active">
                    User
                    </div> -->

                    <div class="card-body">
                        <div class="raw">
                            <div class="col-md-8">
                                <p>Welcome : {{ name }} , <br>
                                    Your email id is {{ email }}
                                </p>
                            </div>
                        </div>  
                        <!-- <button type="submit" @click="logout()" class="btn btn-primary btn-block btn-lg">Logout</button> -->
                        <logout :token='tmp_token' @displayName='ChangeName'></logout>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { API_BASE_URL } from '../const.js'
import Logout from './Logout.vue';
import { Observable } from 'rxjs/Observable'
import 'rxjs/add/observable/interval'
import 'rxjs/add/operator/filter'

    export default {
        components:{Logout},
        data() {
            return {
                name: null,
                email:null,
                tmp_token:localStorage.getItem('token'),
                count:0,
            }
        },
        mounted() {
            console.log('Component mounted.')
            let data=JSON.parse(localStorage.getItem('userdata'));
            this.name=data.user.name;
            this.email=data.user.email;
        },
        methods: {
            ChangeName(value)
            {
                this.name=this.name + ' ' + value;
            }
        },
        created () {
            const obs = Observable.interval(1000)
            obs
            .filter((value) => value % 2 == 0 )
            .subscribe(
                (value)=>this.count = value
            )
        }
    }
</script>
