<template>
  <v-app>
    <!-- Navbar -->
    <v-app-bar dense dark app>
      <v-btn icon @click.stop="mini = !mini">
        <v-app-bar-nav-icon></v-app-bar-nav-icon>
      </v-btn>

      <v-spacer></v-spacer>

      <v-btn @click="logout">
        <v-icon>mdi-logout </v-icon>
        Logout
      </v-btn>
    </v-app-bar>

    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" :mini-variant.sync="mini" dark app>
      <v-list>
        <v-list-item class="px-2">
          <v-list-item-avatar class="rounded-5" height="60" width="60">
            <v-img src="/img/default-profile.png"></v-img>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title>Inventory</v-list-item-title>
            <v-list-item-subtitle>{{ user }}</v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list>
        <v-list-item link to="/dashboard">
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-group
          no-action
          v-if="permissions.user_list || permissions.user_create"
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-account-arrow-right-outline</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>User</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/user/index" v-if="permissions.user_list">
            <v-list-item-content>
              <v-list-item-title>User Record</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/user/create" v-if="permissions.user_create">
            <v-list-item-content>
              <v-list-item-title>Create New</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-group no-action>
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-barcode-scan</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>Product</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/product/index">
            <v-list-item-content>
              <v-list-item-title>Product Lists</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/scan_product">
            <v-list-item-content>
              <v-list-item-title>Scan Product</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-group
          no-action
          v-if="
            permissions.brand_list ||
            permissions.brand_create ||
            permissions.branch_list ||
            permissions.branch_create ||
            permissions.role_list ||
            permissions.role_create ||
            permissions.permission_list ||
            permissions.permission_create
          "
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-cog</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/brand/index">
            <v-list-item-content>
              <v-list-item-title>Brand</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/branch/index">
            <v-list-item-content>
              <v-list-item-title>Branch</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/role/index" v-if="permissions.role_list">
            <v-list-item-content>
              <v-list-item-title>Role</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/permission/index"
            v-if="permissions.permission_list"
          >
            <v-list-item-content>
              <v-list-item-title>Permission</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-item link to="/activity_logs" v-if="permissions.activity_logs">
          <v-list-item-icon>
            <v-icon>mdi-history</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Activity Logs</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>
    <v-overlay :absolute="absolute" :value="overlay">
      <v-progress-circular
        :size="70"
        :width="7"
        color="primary"
        indeterminate
      ></v-progress-circular>
    </v-overlay>
    <!-- Content -->
    <router-view />
  </v-app>
</template>

<script>
let access_token;
let user_permissions;
let user_roles;
import Axios from "axios";

export default {
  data() {
    return {
      absolute: true,
      overlay: false,
      drawer: true,
      mini: false,
      right: null,
      selectedItem: 1,
      user: null,
      loading: null,
      initiated: false,
      permissions: {
        user_list: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
        brand_list: false,
        brand_create: false,
        brand_edit: false,
        brand_delete: false,
        branch_list: false,
        branch_create: false,
        branch_edit: false,
        branch_delete: false,
        role_list: false,
        role_create: false,
        role_edit: false,
        role_delete: false,
        permission_list: false,
        permission_create: false,
        permission_edit: false,
        permission_delete: false,
        activity_logs: false,
      },
      roles: {
        administrator: false,
      },
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getUser() {
      Axios.get("/api/auth/init").then(
        (response) => {
          // console.log(response.data);
          this.user = response.data.user.name;
        },
        (error) => {
          // if unauthenticated (401)
          if (error.response.status == "401") {
            localStorage.removeItem("access_token");
            this.$router.push({ name: "login" });
          }
        }
      );
    },
    logout() {
      this.overlay = true;
      Axios.get("/api/auth/logout").then(
        (response) => {
          if (response.data.success) {
            this.overlay = false;
            localStorage.removeItem("access_token");
            this.$router.push("/login").catch(() => {});
          }
        },
        (error) => {
          this.overlay = false;
          console.log(error);

          // if unauthenticated (401)
          if (error.response.status == "401") {
            localStorage.removeItem("access_token");
            this.$router.push({ name: "login" });
          }
        }
      );
    },
    userRolesPermissions() {
      Axios.get("/api/user/roles_permissions").then((response) => {
        this.user_permissions = response.data.user_permissions;
        this.user_roles = response.data.user_roles;
        this.getRolesPermissions();
      });
    },

    getRolesPermissions() {
      this.permissions.user_list = this.hasPermission(["user-list"]);
      this.permissions.user_create = this.hasPermission(["user-create"]);
      this.permissions.user_edit = this.hasPermission(["user-edit"]);
      this.permissions.user_delete = this.hasPermission(["user-delete"]);
      this.permissions.permission_list = this.hasPermission([
        "permission-list",
      ]);
      this.permissions.permission_create = this.hasPermission([
        "permission-create",
      ]);
      this.permissions.permission_edit = this.hasPermission([
        "permission-edit",
      ]);
      this.permissions.permission_delete = this.hasPermission([
        "permission-delete",
      ]);
      this.permissions.role_list = this.hasPermission(["role-list"]);
      this.permissions.role_create = this.hasPermission(["role-create"]);
      this.permissions.role_edit = this.hasPermission(["role-edit"]);
      this.permissions.role_delete = this.hasPermission(["role-delete"]);
      this.permissions.activity_logs = this.hasPermission(["activity-logs"]);
      this.roles.administrator = this.hasRole(["Administrator"]);
    },

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
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      };
    },
  },

  mounted() {
    Axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");

    this.getUser();
    this.userRolesPermissions();
    // this.websocket();
  },
};
</script>