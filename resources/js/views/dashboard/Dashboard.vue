<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-overlay :absolute="absolute" :value="overlay">
        <v-progress-circular
          :size="70"
          :width="7"
          color="primary"
          indeterminate
        ></v-progress-circular>
      </v-overlay>
      <v-main>
        <v-card>
          <v-card-title> Dashboard </v-card-title>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>
let access_token;
let user_permissions;
let user_roles;

import Axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {},
  data() {
    return {
      absolute: true,
      overlay: false,
      search: "",
      permissions: Home.data().permissions,
      loading: true,
      loading_endorse_history: true,
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    showAlert() {
      this.$swal({
        position: "center",
        icon: "success",
        title: "Record has been saved",
        showConfirmButton: false,
        timer: 2500,
      });
    },

    showConfirmAlert(item) {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Delete record!",
      }).then((result) => {
        // <--

        if (result.value) {
          // <-- if confirmed
          this.$swal({
            position: "center",
            icon: "success",
            title: "Success",
            showConfirmButton: false,
            timer: 2500,
          });
        }
      });
    },

    close() {},

    save() {},
    clear() {},

    userRolesPermissions() {
      Axios.get("api/user/roles_permissions").then((response) => {
        this.user_permissions = response.data.user_permissions;
        this.user_roles = response.data.user_roles;
        this.getRolesPermissions();
      });
    },
    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },

    getRolesPermissions() {},
    hasRole(roles) {

      let hasRole = false;

      roles.forEach((value, index) => {
          hasRole = this.user_roles.includes(value);
      });

      return hasRole;
    },

    hasPermission(permissions) {
    
      let hasPermission = false;

      permissions.forEach((value, index) => {
        hasPermission = this.user_permissions.includes(value);       
      });

      return hasPermission;
    },
    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-create" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      };
    },
  },
  computed: {},
  mounted() {
    Axios.defaults.headers.common["Authorization"] = "Bearer " + localStorage.getItem("access_token");
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>