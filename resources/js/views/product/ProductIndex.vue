<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-main>
        <!-- <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs> -->
        <v-card>
          <v-card-title>
            Product Lists
            <v-btn color="success" class="ml-4" small @click="exportData()" v-if="userPermissions.product_export"
              ><v-icon class="mr-1" small> mdi-microsoft-excel </v-icon
              >Export</v-btn
            >
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <!-- <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn> -->
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
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
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
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
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
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
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
            :items="products"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.product_list"
          >
            <template v-slot:item.brand="{ item }">
              {{ item.brand.name }}
            </template>
            <template v-slot:item.branch="{ item }">
              {{ item.branch.name }}
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editProduct(item)"
                v-if="userPermissions.product_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.product_delete"
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
    editedItem: {
      branch_id: { required },
      brand_id: { required },
      model: { required },
      serial: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Brand", value: "brand" },
        { text: "Model", value: "model" },
        { text: "Serial", value: "serial" },
        { text: "Branch", value: "branch" },
        { text: "Date Created", value: "date_created" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      dialog: false,
      products: [],
      brands: [],
      branches: [],
      userPermissions: Home.data().permissions,
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
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/product/index",
        },
        {
          text: "Product Lists",
          disabled: false,
        },
      ],
      loading: true,
      user_permissions: [],
      user_roles: [],
      user: "",
    };
  },

  methods: {
    getProduct() {
      this.loading = true;
      Axios.get("/api/product/index").then(
        (response) => {
          this.products = response.data.products;
          this.brands = response.data.brands;
          this.branches = response.data.branches;
          this.editedItem.branch_id = response.data.user.branch_id;
          this.user = response.data.user;
          this.loading = false;
         
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editProduct(item) {
      this.editedIndex = this.products.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialog = true;
    },

    deleteProduct(product_id) {
      const data = { product_id: product_id };
      this.loading = true;
      Axios.post("/api/product/delete", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Sockot.IO Server
            // this.$socket.emit("sendData", { action: "product-delete" });
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

          const product_id = item.id;
          const index = this.products.indexOf(item);

          //Call delete Product function
          this.deleteProduct(product_id);

          //Remove item from array products
          this.products.splice(index, 1);

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
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const product_id = this.editedItem.id;

          Axios.post("/api/product/update/" + product_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "product-edit" });

                Object.assign(this.products[this.editedIndex], this.editedItem);
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
          const data = this.editedItem;

          Axios.post("/api/product/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "product-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.products.push(response.data.product);
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
      this.editedItem = Object.assign({}, this.defaultItem);
      this.editedItem.branch_id = this.user.branch_id;
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
    exportData()
    {
      window.open(
        location.origin +
          "/api/product/export/" + this.user.branch_id,
        "_blank"
      );
    },

    getRolesPermissions() {
      this.userPermissions.product_list = this.hasPermission(["product-list"]);
      this.userPermissions.product_create = this.hasPermission([
        "product-create",
      ]);
      this.userPermissions.product_edit = this.hasPermission(["product-edit"]);
      this.userPermissions.product_delete = this.hasPermission([
        "product-delete",
      ]);
      this.userPermissions.product_export = this.hasPermission([
        "product-export",
      ]);

      // hide column actions if user has no permission
      if (
        !this.userPermissions.product_edit &&
        !this.userPermissions.product_delete
      ) {
        this.headers[5].align = " d-none";
      } else {
        this.headers[5].align = "";
      }

      // if user is not authorize
      if (!this.userPermissions.product_list) {
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
    formTitle() {
      return this.editedIndex === -1 ? "New Product" : "Edit Product";
    },
    brandErrors() {
      const errors = [];
      if (!this.$v.editedItem.brand_id.$dirty) return errors;
      !this.$v.editedItem.brand_id.required &&
        errors.push("Brand is required.");
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
      !this.$v.editedItem.branch_id.required &&
        errors.push("Branch is required.");
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
    this.getProduct();
    this.userRolesPermissions();
    this.$barcodeScanner.init(this.onBarcodeScanned);
    // this.websocket();
  },
};
</script>