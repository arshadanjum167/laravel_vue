
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
import VueRouter from 'vue-router'
import axios from 'axios'
import { API_BASE_URL } from './const.js'
import CxltToastr from 'cxlt-vue2-toastr'

Vue.use(VueRouter)
var toastrConfigs = {
  position: 'top right',
  showDuration: 2000,
  timeOut : 3000
}
Vue.use( CxltToastr,toastrConfigs)
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

//Vue.component('example-component', require('./components/ExampleComponent.vue'));
const routes = [
    { path: '/', component: require('./components/ExampleComponent.vue') },
    { path: '/user', component: require('./components/User.vue') },
    { path: '/404', component: require('./components/Notfound.vue') }
  ]

const router = new VueRouter({
    routes 
  })

router.beforeEach((to, from, next) => {
    // redirect to login page if not logged in and trying to access a restricted page
    
    if(to.path!='/404')
    {
      
      const token = localStorage.getItem('token');
      if (!token) 
      {
        
        if(!gettoken())
        {
          return next('/404');
        }
      }
      
      if(token)
      {
        // debugger;
        const userdata = localStorage.getItem('userdata');
        if(userdata!='' && userdata!=null && userdata!=undefined)
        {
          if(to.path=="/")
          {
            return next('/user');  
          }
          else
          {
            return next();
          }
        }
        else
        {
          if(to.path=="/user")
          {
            return next('/');
          }
        }
      }
    }
   return next();
    
})

async function gettoken()
{
  
  try 
      {
        await axios.post(API_BASE_URL + '/get-token')
            .then(response => {
              if(response.data!='')
              {
                const result=response.data;
                // console.log(result.success);
                if(result.success==1)
                {
                  const result_token = result.data.token.token;
                  localStorage.setItem('token', result_token)
                  return true;
                }
                else
                {
                  return false;
                }
              }
              else
              {
                return false;
              }
            }).catch(err => {
            // console.log(err)
            return false;
        })
      } catch (e) {
          // handle the authentication error here
          return false;
          console.log('Error : '+e);
      }
}
// const app = new Vue({
//     el: '#app'
// });
const app = new Vue({
    router
  }).$mount('#app')
