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
        <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>
        <v-card max-width="500px">
          <v-card-title class="mb-0 pb-0">
            <span class="headline">Scan Product</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="ml-4">
            <v-row>
              <v-col cols="11" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="serial"
                  label="Serial"
                  v-model="editedItem.serial"
                  readonly
                  required
                  :error-messages="serialErrors"
                  @input="$v.editedItem.serial.$touch()"
                  @blur="$v.editedItem.serial.$touch()"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="11" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="model"
                  label="Model"
                  v-model="editedItem.model"
                  required
                  :error-messages="modelErrors"
                  @input="$v.editedItem.model.$touch()"
                  @blur="$v.editedItem.model.$touch()"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="11" class="mt-0 mb-0 pt-0 pb-0">
                <v-autocomplete
                  v-model="editedItem.brand_id"
                  :items="brands"
                  item-text="name"
                  item-value="id"
                  label="Brand"
                  required
                  :error-messages="brandErrors"
                  @input="$v.editedItem.brand_id.$touch()"
                  @blur="$v.editedItem.brand_id.$touch()"
                >
                </v-autocomplete>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="11" class="mt-0 mb-0 pt-0 pb-0">
                <v-autocomplete
                  v-model="editedItem.branch_id"
                  :items="branches"
                  item-text="name"
                  item-value="id"
                  label="Branch"
                  required
                  :error-messages="branchErrors"
                  @input="$v.editedItem.branch_id.$touch()"
                  @blur="$v.editedItem.branch_id.$touch()"
                  v-if="user.id === 1"
                >
                </v-autocomplete>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-actions>
            <v-btn
              color="primary"
              @click="save"
              :disabled="disabled"
              class="ml-6 mb-4 mr-1"
            >
              Save
            </v-btn>
            <v-btn color="#E0E0E0" to="/product/index" class="mb-4"> Cancel </v-btn>
          </v-card-actions>
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
import {
  required,
  maxLength,
  email,
  minLength,
  sameAs,
} from "vuelidate/lib/validators";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {
    editedItem: {
      branch_id: { required },
      brand_id: { required },
      model: { required },
      serial: { required },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/product/index",
        },
        {
          text: "Create Product",
          disabled: true,
        },
      ],
      disabled: false,
      roles_permissions: [],
      permissions: Home.data().permissions,
      editedIndex: -1,
      editedItem: {
        branch_id: "",
        brand: "",
        brand_id: "",
        model: "",
        serial: "",
      },
      defaultItem: {
        branch_id: "",
        brand: "",
        brand_id: "",
        model: "",
        serial: "",
      },
      permissions: Home.data().permissions,
      user_permissions: [],
      user_roles: [],
      brands: [],
      branches: [],
      user: "",
    };
  },

  methods: {
    getBrand() {
      Axios.get("/api/product/create").then(
        (response) => {
          this.brands = response.data.brands;
          this.branches = response.data.branches;
          this.editedItem.branch_id = response.data.user.branch_id;
          this.user = response.data.user;

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

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;

        const data = this.editedItem;

        Axios.post("/api/product/store", data).then(
          (response) => {
            console.log(response.data);
            if (response.data.success) {
              // send data to Sockot.IO Server
              // this.$socket.emit("sendData", { action: "product-create" });

              this.showAlert();
              this.clear();

            }
            this.overlay = false;
            this.disabled = false;
          },
          (error) => {
            this.isUnauthorized(error);

            this.overlay = false;
            this.disabled = false;
          }
        );
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
    },

    userRolesPermissions() {
      Axios.get("api/user/roles_permissions").then(
        (response) => {
          this.user_permissions = response.data.user_permissions;
          this.user_roles = response.data.user_roles;
          this.getRolesPermissions();
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },

    getRolesPermissions() {
      this.permissions.user_create = this.hasPermission(["product-create"]);

      // if user is not authorize
      if (!this.permissions.user_create) {
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
    // Create callback function to receive barcode when the scanner is already done
    onBarcodeScanned(barcode) {
      // console.log(barcode);
      this.editedItem.serial = barcode;
      // do something...
    },
    // Reset to the last barcode before hitting enter (whatever anything in the input box)
    resetBarcode() {
      let barcode = this.$barcodeScanner.getPreviousCode();
      // do something...
    },
  },
  computed: {
    brandErrors() {
      const errors = [];
      if (!this.$v.editedItem.brand_id.$dirty) return errors;
      !this.$v.editedItem.brand_id.required && errors.push("Brand is required.");
      return errors;
    },
    modelErrors() {
      const errors = [];
      if (!this.$v.editedItem.model.$dirty) return errors;
      !this.$v.editedItem.model.required && errors.push("Model is required.");
      return errors;
    },
    serialErrors() {
      const errors = [];
      if (!this.$v.editedItem.serial.$dirty) return errors;
      !this.$v.editedItem.serial.required && errors.push("Serial is required.");
      return errors;
    },
    branchErrors() {
      const errors = [];
      if (!this.$v.editedItem.branch_id.$dirty) return errors;
      !this.$v.editedItem.branch_id.required && errors.push("Branch is required.");
      return errors;
    },
  },
  created() {
    // Add barcode scan listener and pass the callback function
    this.$barcodeScanner.init(this.onBarcodeScanned);
  },
  destroyed() {
    // Remove listener when component is destroyed
    this.$barcodeScanner.destroy();
  },
  mounted() {
    Axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getBrand();
    this.userRolesPermissions();
    this.$barcodeScanner.init(this.onBarcodeScanned);
    // this.websocket();
  },
};
</script>