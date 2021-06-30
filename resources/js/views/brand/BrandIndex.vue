<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-main>
        <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>
        <v-card>
          <v-card-title>
            Brand Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="userPermissions.brand_list"
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                  v-if="userPermissions.brand_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="brand"
                              v-model="editedBrand.name"
                              label="Brand"
                              required
                              :error-messages="brandErrors"
                              @input="$v.editedBrand.name.$touch()"
                              @blur="$v.editedBrand.name.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="2" class="mt-0 mb-0 pt-0 pb-0">
                            <v-switch
                              v-model="switch1"
                              :label="activeStatus"
                            ></v-switch>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>

                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" class="mb-4">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="save"
                        class="mb-4 mr-4"
                        :disabled="disabled"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          
          <v-data-table
            :headers="headers"
            :items="brands"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.brand_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editBrand(item)"
                v-if="userPermissions.brand_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.brand_delete"
              >
                mdi-delete
              </v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>
let access_token;

import Axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {
    editedBrand: {
      name: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Brand", value: "name" },
        { text: "Active", value: "active" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      switch1: true,
      disabled: false,
      dialog: false,
      brands: [],
      userPermissions: Home.data().permissions,
      editedIndex: -1,
      editedBrand: {
        name: "",
        active: "Y",
      },
      defaultItem: {
        name: "",
        active: "Y",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Brand Lists",
          disabled: true,
        },
      ],
      loading: true,
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getBrand() {
      this.loading = true;
      Axios.get("/api/brand/index").then(
        (response) => {
          this.brands = response.data.brands;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editBrand(item) {
      this.editedIndex = this.brands.indexOf(item);
      this.editedBrand = Object.assign({}, item);
      this.dialog = true;
    },

    deleteBrand(brand_id) {
      const data = { brand_id: brand_id };
      this.loading = true;
      Axios.post("/api/brand/delete", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Sockot.IO Server
            // this.$socket.emit("sendData", { action: "brand-delete" });
          }
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

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

          const brand_id = item.id;
          const index = this.brands.indexOf(item);

          //Call delete Brand function
          this.deleteBrand(brand_id);

          //Remove item from array brands
          this.brands.splice(index, 1);

          this.$swal({
            position: "center",
            icon: "success",
            title: "Record has been deleted",
            showConfirmButton: false,
            timer: 2500,
          });
        }
      });
    },

    close() {
      this.dialog = false;
      this.clear();
      this.$nextTick(() => {
        this.editedBrand = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedBrand;
          const brand_id = this.editedBrand.id;

          Axios.post("/api/brand/update/" + brand_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "brand-edit" });

                Object.assign(
                  this.brands[this.editedIndex],
                  this.editedBrand
                );
                this.showAlert();
                this.close();
              }

              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        } else {
          const data = this.editedBrand;

          Axios.post("/api/brand/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "brand-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.brands.push(response.data.brand);
              }
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        }
      }
    },

    clear() {
      this.$v.$reset();
      this.editedBrand.name = "";
    },
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

    getRolesPermissions() {
      this.userPermissions.brand_list = this.hasPermission([
        "brand-list",
      ]);
      this.userPermissions.brand_create = this.hasPermission([
        "brand-create",
      ]);
      this.userPermissions.brand_edit = this.hasPermission([
        "brand-edit",
      ]);
      this.userPermissions.brand_delete = this.hasPermission([
        "brand-delete",
      ]);

      // hide column actions if user has no permission
      if (
        !this.userPermissions.brand_edit &&
        !this.userPermissions.brand_delete
      ) {
        this.headers[2].align = " d-none";
      } else {
        this.headers[2].align = "";
      }

      // if user is not authorize
      if (
        !this.userPermissions.brand_list &&
        !this.userPermissions.brand_create
      ) {
        this.$router.push("/unauthorize").catch(() => {});
      }
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
          action == "permission-create" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Brand" : "Edit Brand";
    },
    brandErrors() {
      const errors = [];
      if (!this.$v.editedBrand.name.$dirty) return errors;
      !this.$v.editedBrand.name.required &&
        errors.push("Brand is required.");
      return errors;
    },
    activeStatus() {
      if (this.switch1) {
        this.editedBrand.active = "Y";
        return " Active";
      } else {
        this.editedBrand.active = "N";
        return " Inactive";
      }
    },
  },
  mounted() {
    Axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getBrand();
    this.userRolesPermissions();
    // this.websocket();
  },
};
</script>